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
 * well. This package also provides a set of built-in contexts (frameworks,
 * CMSs, configs etc.) that can be used to update the application.
 *
 * ** Providers **
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
 * ** Contexts **
 *
 * Contexts are used to apply environment-specific changes to the application.
 *
 * The active context is determined by the "active" context - a registered
 * context that has detected the current context using its own logic.
 *
 * A context may provide generic changes that are applied to the application.
 * A provider may provide provider-specific context changes that are applied to
 * the application as well.
 *
 * For example, a Drupal context applies the changes to the global $settings
 * array, while a Lagoon provider's contextualize() method adds more
 * Lagoon-specific changes to the $settings array. In this case, Acquia Cloud
 * provider provides their own Acquia Cloud-specific changes to the $settings
 * array.
 *
 * The goal of this package is to have enough context changes to cover the most
 * common use cases, but also to allow adding custom contexts to cover the
 * specific use cases within the application.
 *
 * ** ENVIRONMENT_TYPE **
 *
 * The detected environment type is stored in the `ENVIRONMENT_TYPE` environment
 * variable. This variable can be used in the application to apply environment-
 * specific changes.
 *
 * If the `ENVIRONMENT_TYPE` environment variable is already set, the value
 * will be used as the environment type. This is useful in cases when the
 * environment type needs to be set manually to override the detected type (for
 * example, when debugging the application).
 *
 * ** Usage **
 *
 * @code
 * Environment::init();                    // Init and populate the `ENVIRONMENT_TYPE` env var.
 * $env_type = getenv('ENVIRONMENT_TYPE'); // Use the `ENVIRONMENT_TYPE` env var as needed.
 * ...
 * if ($env_type === Environment::LOCAL) {
 *  // Apply local settings.
 * }
 * @endcode
 *
 * ** Shortcuts **
 *
 * @code
 * Environment::init();
 * if (Environment::isLocal()) {
 *   // Apply local settings.
 * }
 * @endcode
 *
 * @code
 * Environment::init();
 * if (Environment::is('custom_type')) {
 *   // Apply settings for a custom type.
 * }
 * @endcode
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
   *
   * @var \DrevOps\EnvironmentDetector\Providers\ProviderInterface[]
   */
  protected static array $providers = [];

  /**
   * The "active" context. Only one context can be active at a time.
   */
  protected static ?ContextInterface $context = NULL;

  /**
   * The list of registered contexts.
   *
   * @var \DrevOps\EnvironmentDetector\Contexts\ContextInterface[]
   */
  protected static array $contexts = [];

  /**
   * The override callback to change the environment type.
   */
  protected static mixed $override = NULL;

  /**
   * Initialize the environment detector.
   *
   * This is a main entry point to the environment detector and should be used
   * in the most cases to initialize the environment.
   *
   * @code
   * Environment::init();
   * @endcode
   *
   * Or to skip applying some of the context changes.
   * @code
   * Environment::init(FALSE);
   * @endcode
   */
  public static function init(bool $contextualize = TRUE): void {
    static::type();

    if ($contextualize) {
      static::contextualize();
    }
  }

  /**
   * Check if the current environment is local.
   */
  public static function isLocal(): bool {
    return static::is(self::LOCAL);
  }

  /**
   * Check if the current environment is CI.
   */
  public static function isCi(): bool {
    return static::is(self::CI);
  }

  /**
   * Check if the current environment is development.
   */
  public static function isDev(): bool {
    return static::is(self::DEVELOPMENT);
  }

  /**
   * Check if the current environment is preview.
   */
  public static function isPreview(): bool {
    return static::is(self::PREVIEW);
  }

  /**
   * Check if the current environment is stage.
   */
  public static function isStage(): bool {
    return static::is(self::STAGE);
  }

  /**
   * Check if the current environment is production.
   */
  public static function isProd(): bool {
    return static::is(self::PRODUCTION);
  }

  /**
   * Check if the current environment is of a specific type.
   *
   * @param string $type
   *   The environment type to check.
   *
   * @return bool
   *   TRUE if the current environment is of the provided type, FALSE otherwise.
   */
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
    // Type can already be set by the environment variable.
    // This will prevent the provider from identifying the environment type.
    // But will still allow to apply the provider-specific context changes.
    $type = getenv('ENVIRONMENT_TYPE');
    if ($type) {
      static::$type = $type;
    }
    else {
      if (!static::$type) {
        static::$type = static::provider()?->type();

        if (static::$override && is_callable(static::$override)) {
          static::$type = (static::$override)(static::$provider, static::$type);
        }
      }

      static::$type = static::$type ?: static::$fallback;
      putenv('ENVIRONMENT_TYPE=' . static::$type);
    }

    return static::$type;
  }

  /**
   * Set the override callback to change the environment type.
   *
   * @param callable|array<string> $callback
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
   * Get the active provider.
   *
   * @return \DrevOps\EnvironmentDetector\Providers\ProviderInterface
   *   The active provider.
   */
  public static function provider(): ?ProviderInterface {
    if (!static::$provider instanceof ProviderInterface) {
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
   *   An array of directories to scan for provider classes. This package's
   *   default providers are registered by default.
   *
   * @return \DrevOps\EnvironmentDetector\Providers\ProviderInterface[]
   *   An array of registered providers.
   *
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
            $provider = new $class();
            assert($provider instanceof ProviderInterface);
            static::addProvider($provider);
          }
        }
      }

      if (empty(static::$providers)) {
        // We want to throw an exception if no environment providers were
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
   * @param \DrevOps\EnvironmentDetector\Providers\ProviderInterface $provider
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
   * Apply the active context.
   *
   * @code
   * Environment::contextualize();
   * @endcode
   */
  public static function contextualize(): void {
    $context = static::context();
    if ($context instanceof ContextInterface) {
      // Apply generic context changes.
      $context->contextualize();
      // Apply provider-specific context changes.
      static::provider()?->contextualize($context);
    }
  }

  /**
   * Get the active context.
   *
   * @return \DrevOps\EnvironmentDetector\Contexts\ContextInterface
   *   The active context.
   */
  public static function context(): ?ContextInterface {
    if (!static::$context instanceof ContextInterface) {
      $active = array_filter(static::contexts(), function (ContextInterface $context): bool {
        return $context->active();
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
   *   An array of directories to scan for context classes. This package's
   *   default contexts are registered by default.
   *
   * @return \DrevOps\EnvironmentDetector\Contexts\ContextInterface[]
   *   An array of registered contexts.
   *
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
            $context = new $class();
            assert($context instanceof ContextInterface);
            static::addContext($context);
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
   * @param \DrevOps\EnvironmentDetector\Contexts\ContextInterface $context
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

  /**
   * Prevent creating an instance of this class.
   */
  // phpcs:disable DrupalPractice.Commenting.CommentEmptyLine.SpacingAfter
  // phpcs:disable Drupal.Commenting.FunctionComment.WrongStyle
  // phpcs:disable Squiz.WhiteSpace.FunctionSpacing.After
  // @codeCoverageIgnoreStart
  private function __construct() {
  }

  /**
   * Prevent cloning this class.
   */
  private function __clone() {
  }
  // @codeCoverageIgnoreEnd
  // phpcs:enable DrupalPractice.Commenting.CommentEmptyLine.SpacingAfter
  // phpcs:enable Drupal.Commenting.FunctionComment.WrongStyle
  // phpcs:enable Squiz.WhiteSpace.FunctionSpacing.After

}
