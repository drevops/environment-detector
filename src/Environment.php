<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector;

use DrevOps\EnvironmentDetector\Contexts\ContextInterface;
use DrevOps\EnvironmentDetector\Providers\ProviderInterface;

/**
 * Universal environment detector.
 *
 * Detects the environment type based on the registered providers. This package
 * provides a set of built-in providers, but custom providers can be added as
 * well.
 *
 * The environment type is determined by the "active" provider - a registered
 * provider that has detected the current environment using its own logic.
 * Only one provider can be active at a time (otherwise an exception is thrown).
 *
 * If no provider is active an exception is thrown. This is to ensure that the
 * environment type is always detected and that the application does not run
 * with an unknown environment type. Add a custom provider using ::addProvider()
 * to register a new provider implementing ProviderInterface.
 *
 * The environment type returned by a provider can be overridden by a callback
 * set using the ::setOverride() method. The callback will receive the
 * currently active provider, and the currently discovered environment type as
 * arguments. This allows to add custom types and override the detected type
 * based on the custom logic. The advantage of such an approach is that the
 * active provider is still discovered using the provider's own logic, and the
 * override callback is used only to change the environment type.
 *
 * If an active provider is not able to determine the environment type, it
 * returns NULL. In this case, the fallback environment type is used.
 * The fallback environment type can be overridden using the ::setFallback()
 * method.
 * The default fallback environment type is Environment::DEV - this is to make
 * sure that, in case of misconfiguration, the application does not apply local
 * settings in production or production settings in local - 'dev' type is
 * the safest default.
 *
 * The discovered type is statically cached to be performant. The cache can be
 * reset using the ::reset() method, which will reset the detected type and the
 * active provider, but will preserver the registered providers.
 * Call ::reset(TRUE) to reset all registered providers as well.
 *
 * @package DrevOps\EnvironmentDetector
 */
class Environment {

  /**
   * Defines a local environment.
   */
  public const LOCAL = 'local';

  /**
   * Defines a CI environment.
   */
  public const CI = 'ci';

  /**
   * Defines a development environment.
   */
  public const DEVELOPMENT = 'development';

  /**
   * Defines a temporary preview environment.
   */
  public const PREVIEW = 'preview';

  /**
   * Defines a stage environment.
   */
  public const STAGE = 'stage';

  /**
   * Defines a production environment.
   */
  public const PRODUCTION = 'production';

  /**
   * The current environment type.
   */
  protected static ?string $type = NULL;

  /**
   * The fallback environment type.
   */
  protected static string $fallback = self::DEVELOPMENT;

  /**
   * The "active" provider. Only one provider can be active at a time.
   */
  protected static ?ProviderInterface $provider = NULL;

  /**
   * The list of registered providers.
   */
  protected static array $providers = [];

  /**
   * The "active" context. Only one context can be active at a time.
   */
  protected static ?ContextInterface $context = NULL;

  /**
   * The list of registered contexts.
   */
  protected static array $contexts = [];

  /**
   * The override callback to change the environment type.
   */
  protected static mixed $override = NULL;

  // @codeCoverageIgnoreStart
  private function __construct() {
  }

  private function __clone() {
  }

  // @codeCoverageIgnoreEnd

  public static function isLocal(): bool {
    return static::is(self::LOCAL);
  }

  public static function isCi(): bool {
    return static::is(self::CI);
  }

  public static function isDev(): bool {
    return static::is(self::DEVELOPMENT);
  }

  public static function isPreview(): bool {
    return static::is(self::PREVIEW);
  }

  public static function isStage(): bool {
    return static::is(self::STAGE);
  }

  public static function isProd(): bool {
    return static::is(self::PRODUCTION);
  }

  public static function is(string $type): bool {
    return static::type() === $type;
  }

  /**
   * Get the current environment type.
   *
   * Avoid using this method directly, use the ::is*() or ::is() methods
   * instead.
   *
   * @return string
   *   The environment type.
   */
  public static function type(): string {
    if (!static::$type) {
      static::$type = static::provider()->type();

      if (static::$override && is_callable(static::$override)) {
        static::$type = (static::$override)(static::$provider, static::$type);
      }
    }

    return static::$type ?: static::$fallback;
  }

  /**
   * Set the override callback to change the environment type.
   *
   * @param callable|array $callback
   *   The callback to change the environment type. Callback will receive the
   *   active provider, if any, and the currently discovered environment type
   *   as arguments.
   */
  public static function setOverride(callable|array $callback): void {
    if (!is_callable($callback)) {
      throw new \InvalidArgumentException('The callback must be callable');
    }
    static::$override = $callback;
  }

  /**
   * Get the active provider.
   *
   * @return ProviderInterface
   *   The active provider.
   */
  public static function provider(): ?ProviderInterface {
    if (!static::$provider instanceof ProviderInterface) {
      // Collect all active providers.
      $active = array_filter(static::providers(), function (ProviderInterface $provider): bool {
        return $provider->active();
      });

      if (count($active) > 1) {
        throw new \Exception('Multiple active environment providers detected');
      }

      static::$provider = array_shift($active);
    }

    return static::$provider;
  }

  /**
   * Get the list of registered providers.
   *
   * @param array<int|string,string> $dirs
   *   An array of directories to scan for provider classes. This paackage's
   *   default providers are registered by default.
   *
   * @return ProviderInterface[]
   *   An array of registered providers.
   * @throws \RuntimeException
   *   If no environment providers were registered.
   */
  public static function providers(array $dirs = []): array {
    if (!static::$providers) {
      $dirs = array_merge(['default' => __DIR__ . '/Providers'], $dirs);

      foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
          continue;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
          $class = 'DrevOps\\EnvironmentDetector\\Providers\\' . pathinfo($file, PATHINFO_FILENAME);
          if (class_exists($class) && in_array(ProviderInterface::class, class_implements($class)) && !(new \ReflectionClass($class))->isAbstract()) {
            static::addProvider(new $class());
          }
        }
      }

      if (empty(static::$providers)) {
        // We want to throw an exception if No environment providers were
        // registered rather than relying on a "default" provider, as this is a
        // sign of a severe misconfiguration, and we want to hard-fail the
        // application.
        // This is a safer approach than resolving to an incorrect environment
        // type and silently leading to unexpected behavior within the
        // application.
        throw new \RuntimeException('No environment providers were registered');
      }
    }

    return static::$providers;
  }

  /**
   * Add a custom provider.
   *
   * @param ProviderInterface $provider
   *   The provider to add.
   *
   * @throws \InvalidArgumentException
   *   If a provider with the same ID is already registered.
   */
  public static function addProvider(ProviderInterface $provider): void {
    foreach (static::$providers as $existing) {
      if ($existing->id() === $provider->id()) {
        throw new \InvalidArgumentException(sprintf('Provider with ID "%s" is already registered', $provider->id()));
      }
    }

    static::$providers[] = $provider;
    // Reset the detected environment type to make sure it is recalculated
    // based on the new provider.
    static::$provider = NULL;
    static::$type = NULL;
  }

  /**
   * Get the fallback environment type.
   */
  public static function fallback(): string {
    return static::$fallback;
  }

  /**
   * Set the fallback environment type.
   *
   * @param string $type
   *   The fallback environment type.
   */
  public static function setFallback(string $type): void {
    static::$fallback = $type;
  }

  /**
   * Apply the active context to the provided data.
   *
   * @code
   * Environment::applyContext($data1, $data2, $data3, $data4);
   * @endcode
   *
   * @param mixed $data
   *   The data to apply the context to.
   */
  public static function applyContext(?array &$data = NULL): void {
    $context = static::context($data);
    if ($context instanceof ContextInterface) {
      static::provider()->applyContext($context, $data);
    }
  }

  /**
   * Get the active context.
   *
   * @return ContextInterface
   *   The active context.
   */
  public static function context(?array $data = NULL): ?ContextInterface {
    if (!static::$context instanceof ContextInterface) {
      // Collect all active contexts.
      $active = array_filter(static::contexts(), function (ContextInterface $context) use ($data): bool {
        return $context->active($data);
      });

      if (count($active) > 1) {
        throw new \Exception('Multiple active contexts detected');
      }

      static::$context = array_shift($active);
    }

    return static::$context;
  }

  /**
   * Get the list of registered contexts.
   *
   * @param array<int|string,string> $dirs
   *   An array of directories to scan for context classes. This paackage's
   *   default contexts are registered by default.
   *
   * @return \DrevOps\EnvironmentDetector\Contexts\ContextInterface[]
   *   An array of registered contexts.
   * @throws \RuntimeException
   *   If no environment contexts were registered.
   */
  public static function contexts(array $dirs = []): array {
    if (!static::$contexts) {
      $dirs = array_merge(['default' => __DIR__ . '/Contexts'], $dirs);

      foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
          continue;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
          $class = 'DrevOps\\EnvironmentDetector\\Contexts\\' . pathinfo($file, PATHINFO_FILENAME);
          if (class_exists($class) && in_array(ContextInterface::class, class_implements($class)) && !(new \ReflectionClass($class))->isAbstract()) {
            static::addContext(new $class());
          }
        }
      }

      if (empty(static::$contexts)) {
        // We want to throw an exception if no environment contexts were
        // registered rather than relying on a "default" context, as this is a
        // sign of a severe misconfiguration, and we want to hard-fail the
        // application.
        // This is a safer approach than resolving to an incorrect context
        // and silently leading to unexpected behavior within the application.
        throw new \RuntimeException('No contexts were registered');
      }
    }

    return static::$contexts;
  }

  /**
   * Add a custom context.
   *
   * @param ContextInterface $context
   *   The context to add.
   *
   * @throws \InvalidArgumentException
   *   If a context with the same ID is already registered.
   */
  public static function addContext(ContextInterface $context): void {
    foreach (static::$contexts as $existing) {
      if ($existing->id() === $context->id()) {
        throw new \InvalidArgumentException(sprintf('Context with ID "%s" is already registered', $context->id()));
      }
    }

    static::$contexts[] = $context;
    static::$context = NULL;
  }

  /**
   * Reset the detected environment type.
   *
   * @param bool $all
   *   If TRUE, reset all registered providers as well.
   */
  public static function reset(bool $all = TRUE): void {
    static::$type = NULL;
    static::$provider = NULL;
    static::$context = NULL;
    static::$override = NULL;
    static::$fallback = self::DEVELOPMENT;

    if ($all) {
      static::$providers = [];
      static::$contexts = [];
    }
  }

}
