<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector;

use DrevOps\EnvironmentDetector\Providers\Acquia;
use DrevOps\EnvironmentDetector\Providers\CircleCi;
use DrevOps\EnvironmentDetector\Providers\Ddev;
use DrevOps\EnvironmentDetector\Providers\Docker;
use DrevOps\EnvironmentDetector\Providers\GitHubActions;
use DrevOps\EnvironmentDetector\Providers\GitLabCi;
use DrevOps\EnvironmentDetector\Providers\Lagoon;
use DrevOps\EnvironmentDetector\Providers\Lando;
use DrevOps\EnvironmentDetector\Providers\Pantheon;
use DrevOps\EnvironmentDetector\Providers\PlatformSh;
use DrevOps\EnvironmentDetector\Providers\Skpr;
use DrevOps\EnvironmentDetector\Providers\Tugboat;
use DrevOps\EnvironmentDetector\Contexts\Drupal;
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
 * *** Direct usage with per-environment shortcuts: ***
 *
 * @code
 * if (Environment::isLocal()) {
 *   // Apply local settings.
 * }
 *
 * if (Environment::isProd()) {
 *   // Apply production settings.
 * }
 * @endcode
 *
 * *** Alternative usage with an environment variable: ***
 * @code
 * Environment::init();                                      // Init and populate the `ENVIRONMENT_TYPE` env var.
 * if (getenv('ENVIRONMENT_TYPE') === Environment::LOCAL) {  // Use the `ENVIRONMENT_TYPE` env var as needed.
 *  // Apply local settings.
 * }
 * @endcode
 *
 * *** Advanced usage with customization before initialization: ***
 * @code
 * Environment::init(
 *   contextualize: TRUE,                             // Whether to apply the context automatically when the environment type is requested.
 *   fallback: Environment::DEVELOPMENT               // The fallback environment type.
 *   override_callback: function($provider, $type) {  // The override callback to change the environment type.
 *     // Custom logic to override the detected environment type.
 *    return $type;
 *   }
 * );
 * if (getenv('ENVIRONMENT_TYPE') === Environment::LOCAL) {  // Use the `ENVIRONMENT_TYPE` env var as needed.
 *   // Apply local settings.
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
   * Pre-defined provider classes.
   *
   * @var array<string>
   */
  protected const PROVIDERS = [
    Acquia::class,
    CircleCi::class,
    Ddev::class,
    Docker::class,
    GitHubActions::class,
    GitLabCi::class,
    Lagoon::class,
    Lando::class,
    Pantheon::class,
    PlatformSh::class,
    Skpr::class,
    Tugboat::class,
  ];

  /**
   * Pre-defined context classes.
   *
   * @var array<string>
   */
  protected const CONTEXTS = [
    Drupal::class,
  ];

  /**
   * The fallback environment type.
   */
  protected static string $fallback = self::DEVELOPMENT;

  /**
   * The override callback to change the environment type.
   */
  protected static mixed $override = NULL;

  /**
   * The "active" provider. Only one provider can be active at a time.
   */
  protected static ?ProviderInterface $provider = NULL;

  /**
   * The list of registered providers.
   *
   * @var \DrevOps\EnvironmentDetector\Providers\ProviderInterface[]|null
   */
  protected static ?array $providers = NULL;

  /**
   * The "active" context. Only one context can be active at a time.
   */
  protected static ?ContextInterface $context = NULL;

  /**
   * The list of registered contexts.
   *
   * @var \DrevOps\EnvironmentDetector\Contexts\ContextInterface[]|null
   */
  protected static ?array $contexts = NULL;

  /**
   * Flag indicating whether this instance has been initialized.
   */
  protected static bool $isInitialized = FALSE;

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
    static::init();

    return getenv('ENVIRONMENT_TYPE') === $type;
  }

  /**
   * Initialize the environment detector.
   *
   * Use this only if you need to configure the environment detector before
   * using it. Otherwise, use the ::is*() or ::is() methods directly.
   *
   * @code
   * Environment::init(
   *   contextualize: TRUE,                             // Whether to apply the context automatically when the environment type is requested.
   *   fallback: Environment::DEVELOPMENT               // The fallback environment type.
   *   override_callback: function($provider, $type) {  // The override callback to change the environment type.
   *     // Custom logic to override the detected environment type.
   *    return $type;
   *   },
   *   providers: [MyCustomProvider::class],            // An array of additional provider classes to register.
   *   contexts: [MyCustomContext::class],              // An array of additional context classes to register.
   * );
   * @endcode
   *
   * @param bool $contextualize
   *   Whether to apply the context automatically when the environment type is
   *   requested. Set to FALSE to prevent automatic contextualization. In this
   *   case, call ::contextualize() manually to apply the context.
   * @param string $fallback
   *   The fallback environment type to use if the active provider is not able
   *   to determine the environment type. Default is Environment::DEVELOPMENT.
   * @param callable|array<mixed,string>|null $override
   *   The override callback to change the environment type. The callback will
   *   receive the currently active provider, and the currently discovered
   *   environment type as arguments. This allows to add custom types and
   *   override the detected type based on the custom logic. The advantage of
   *   such an approach is that the active provider is still discovered using
   *   the provider's own logic, and the override callback is used only to
   *   change the environment type.
   * @param array<int,\DrevOps\EnvironmentDetector\Providers\ProviderInterface> $providers
   *   An array of additional provider classes to register.
   * @param array<int,\DrevOps\EnvironmentDetector\Contexts\ContextInterface> $contexts
   *   An array of additional context classes to register.
   */
  public static function init(
    bool $contextualize = TRUE,
    string $fallback = self::DEVELOPMENT,
    callable|array|null $override = NULL,
    array $providers = [],
    array $contexts = [],
  ): void {
    if (static::$isInitialized) {
      return;
    }

    static::$fallback = $fallback;

    if ($override) {
      if (!is_callable($override)) {
        throw new \InvalidArgumentException('The callback must be callable');
      }
      static::$override = $override;
    }

    static::collectProviders($providers);
    static::collectContexts($contexts);

    static::discoverType();

    if ($contextualize) {
      static::applyActiveContext();
    }

    static::$isInitialized = TRUE;
  }

  /**
   * Reset the detected environment type.
   *
   * @param bool $all
   *   Whether to reset all settings.
   */
  public static function reset(bool $all = FALSE): void {
    static::$provider = NULL;
    static::$context = NULL;
    static::$providers = NULL;
    static::$contexts = NULL;
    static::$isInitialized = FALSE;

    if ($all) {
      static::$fallback = self::DEVELOPMENT;
      static::$override = NULL;
    }
  }

  /**
   * Get the current environment type.
   *
   * Use `getenv('ENVIRONMENT_TYPE')` to get the environment type.
   *
   * @return string
   *   The environment type.
   */
  protected static function discoverType(): string {
    $type = getenv('ENVIRONMENT_TYPE');

    if (!$type) {
      $type = static::getActiveProvider()?->type();

      if (static::$override && is_callable(static::$override)) {
        $type = (static::$override)(static::$provider, $type);
      }

      $type = $type ?: static::$fallback;

      putenv('ENVIRONMENT_TYPE=' . $type);
    }

    return $type;
  }

  /**
   * Get the active provider.
   *
   * @return \DrevOps\EnvironmentDetector\Providers\ProviderInterface
   *   The active provider.
   */
  public static function getActiveProvider(): ?ProviderInterface {
    if (!static::$provider instanceof ProviderInterface) {
      $active = NULL;

      // Ensure at most one active provider exists.
      // Stop checking as soon as more than one is detected to avoid unnecessary
      // evaluations.
      $providers = static::collectProviders();
      foreach ($providers as $provider) {
        if ($provider->active()) {
          if ($active !== NULL) {
            throw new \Exception('Multiple active environment providers detected: ' . $active->id() . ' and ' . $provider->id());
          }
          $active = $provider;
        }
      }

      static::$provider = $active;
    }

    return static::$provider;
  }

  /**
   * Get the list of registered providers.
   *
   * @param array<int,\DrevOps\EnvironmentDetector\Providers\ProviderInterface> $additional
   *   An array of additional provider classes to register.
   *
   * @return \DrevOps\EnvironmentDetector\Providers\ProviderInterface[]
   *   An array of registered providers.
   */
  protected static function collectProviders(array $additional = []): array {
    if (!static::$providers) {
      static::$providers = [];

      $instances = array_merge(self::PROVIDERS, $additional);

      foreach ($instances as $instance) {
        $instance = is_string($instance) ? new $instance() : $instance;

        if (!($instance instanceof ProviderInterface)) {
          throw new \InvalidArgumentException('The provider must implement ProviderInterface');
        }

        static::addProvider($instance);
      }

      static::$providers ??= [];
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
  protected static function addProvider(ProviderInterface $provider): void {
    if (array_key_exists($provider->id(), static::$providers ?? [])) {
      throw new \InvalidArgumentException(sprintf('Provider with ID "%s" is already registered', $provider->id()));
    }

    static::$providers[$provider->id()] = $provider;
    // Reset the detected environment type to make sure it is recalculated
    // based on the new provider.
    static::$provider = NULL;
  }

  /**
   * Apply the active context.
   */
  protected static function applyActiveContext(): void {
    $context = static::getActiveContext();
    if ($context instanceof ContextInterface) {
      // Apply generic context changes.
      $context->contextualize();
      // Apply provider-specific context changes.
      static::getActiveProvider()?->contextualize($context);
    }
  }

  /**
   * Get the active context.
   *
   * @return \DrevOps\EnvironmentDetector\Contexts\ContextInterface
   *   The active context.
   */
  public static function getActiveContext(): ?ContextInterface {
    if (!static::$context instanceof ContextInterface) {
      $active = NULL;

      // Ensure at most one active context exists.
      // Stop checking as soon as more than one is detected to avoid unnecessary
      // evaluations.
      $contexts = static::collectContexts();
      foreach ($contexts as $context) {
        if ($context->active()) {
          if ($active !== NULL) {
            throw new \Exception('Multiple active contexts detected: ' . $active->id() . ' and ' . $context->id());
          }
          $active = $context;
        }
      }

      static::$context = $active;
    }

    return static::$context;
  }

  /**
   * Get the list of registered contexts.
   *
   * @param array<int, \DrevOps\EnvironmentDetector\Contexts\ContextInterface> $additional
   *   An array of additional context classes to register.
   *
   * @return \DrevOps\EnvironmentDetector\Contexts\ContextInterface[]
   *   An array of registered contexts.
   */
  protected static function collectContexts(array $additional = []): array {
    if (!static::$contexts) {
      static::$contexts = [];

      $instances = array_merge(self::CONTEXTS, $additional);

      foreach ($instances as $instance) {
        $instance = is_string($instance) ? new $instance() : $instance;

        if (!($instance instanceof ContextInterface)) {
          throw new \InvalidArgumentException('The context must implement ContextInterface');
        }

        static::addContext($instance);
      }

      static::$contexts ??= [];
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
  protected static function addContext(ContextInterface $context): void {
    if (array_key_exists($context->id(), static::$contexts ?? [])) {
      throw new \InvalidArgumentException(sprintf('Context with ID "%s" is already registered', $context->id()));
    }

    static::$contexts[$context->id()] = $context;
    // Reset the detected context to make sure it is recalculated.
    static::$context = NULL;
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
