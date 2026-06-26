<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector;

use DrevOps\EnvironmentDetector\Contexts\ContextInterface;
use DrevOps\EnvironmentDetector\Contexts\Drupal;
use DrevOps\EnvironmentDetector\Platforms\Acquia;
use DrevOps\EnvironmentDetector\Platforms\CircleCi;
use DrevOps\EnvironmentDetector\Platforms\GitHubActions;
use DrevOps\EnvironmentDetector\Platforms\GitLabCi;
use DrevOps\EnvironmentDetector\Platforms\Lagoon;
use DrevOps\EnvironmentDetector\Platforms\Pantheon;
use DrevOps\EnvironmentDetector\Platforms\PlatformInterface;
use DrevOps\EnvironmentDetector\Platforms\PlatformSh;
use DrevOps\EnvironmentDetector\Platforms\Skpr;
use DrevOps\EnvironmentDetector\Platforms\Tugboat;
use DrevOps\EnvironmentDetector\Stacks\Container;
use DrevOps\EnvironmentDetector\Stacks\Ddev;
use DrevOps\EnvironmentDetector\Stacks\Lando;
use DrevOps\EnvironmentDetector\Stacks\Native;
use DrevOps\EnvironmentDetector\Stacks\StackInterface;

/**
 * Universal environment detector.
 *
 * Detects the environment type using nested detector rings. A run wraps from an
 * outer ring down to the application: a platform contains a stack, which
 * contains the runtime, which contains the application context. This package
 * ships built-in platforms and stacks, but custom ones can be added too.
 *
 * ** Platforms **
 *
 * A platform is the outermost ring - the hosting provider (Acquia, Lagoon, ...)
 * or the CI service (GitHub Actions, ...). It is the ONLY ring that carries the
 * environment type. The type is read off the "active" platform - a registered
 * platform that has detected the current environment using its own logic. At
 * most one platform can be active at a time; two active platforms is a genuine
 * misconfiguration and throws. Add a custom platform via ::init(platforms: ...)
 * to register a class implementing PlatformInterface.
 *
 * If no platform is active, the type is `ci` when a generic `CI` signal is
 * present, otherwise `local` - an empty outer ring means the run is local.
 *
 * If an active platform cannot determine the environment type, it returns NULL.
 * In this case, the fallback environment type is used. The default fallback is
 * Environment::DEVELOPMENT - this makes sure that, in case of misconfiguration,
 * the application does not apply local settings in production or production
 * settings in local; 'development' is the safest default.
 *
 * ** Stacks **
 *
 * A stack is an inner ring - the substrate the environment runs in (a
 * container, or a more specific container). Stacks never carry the environment
 * type and never collide with a platform: a container inside Acquia or inside
 * CI is just an inner ring. The substrate is always resolved: exactly one stack
 * is active at a time - the most specific container that matches, or the native
 * host on bare metal. The active stack may contribute settings to the active
 * context.
 *
 * ** Contexts **
 *
 * Contexts are the application/framework where settings land. A context applies
 * generic changes to the application; the active platform and the active stack
 * may then apply their own context-specific changes on top of the same target.
 * At most one context can be active at a time.
 *
 * The goal of this package is to have enough context changes to cover the most
 * common use cases, but also to allow adding custom contexts to cover the
 * specific use cases within the application.
 *
 * The discovered type is statically cached to be performant. The cache can be
 * reset using the ::reset() method, which resets the active platform, stack
 * and context and the registered platforms, stacks and contexts. Call
 * ::reset(TRUE) to also reset the fallback type.
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
 *   fallback: Environment::DEVELOPMENT,              // The fallback environment type.
 *   platforms: [MyCustomPlatform::class],            // An array of additional platform classes to register.
 *   stacks: [MyCustomStack::class],                  // An array of additional stack classes to register.
 *   contexts: [MyCustomContext::class],              // An array of additional context classes to register.
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
   * Pre-defined platform classes.
   *
   * @var array<string>
   */
  protected const PLATFORMS = [
    Acquia::class,
    CircleCi::class,
    GitHubActions::class,
    GitLabCi::class,
    Lagoon::class,
    Pantheon::class,
    PlatformSh::class,
    Skpr::class,
    Tugboat::class,
  ];

  /**
   * Pre-defined specific stack classes, ordered most specific first.
   *
   * The generic Container and Native fallbacks are appended in
   * collectStacks() so they always run after these and any user additions.
   *
   * @var array<string>
   */
  protected const STACKS = [
    Ddev::class,
    Lando::class,
  ];

  /**
   * Pre-defined context classes.
   *
   * @var array<class-string<ContextInterface>>
   */
  protected const CONTEXTS = [
    Drupal::class,
  ];

  /**
   * The fallback environment type.
   */
  protected static string $fallback = self::DEVELOPMENT;

  /**
   * The "active" platform. Only one platform can be active at a time.
   */
  protected static ?PlatformInterface $platform = NULL;

  /**
   * The list of registered platforms.
   *
   * @var \DrevOps\EnvironmentDetector\Platforms\PlatformInterface[]|null
   */
  protected static ?array $platforms = NULL;

  /**
   * The "active" stack. Only one stack can be active at a time.
   */
  protected static ?StackInterface $stack = NULL;

  /**
   * The list of registered stacks.
   *
   * @var \DrevOps\EnvironmentDetector\Stacks\StackInterface[]|null
   */
  protected static ?array $stacks = NULL;

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
   *   fallback: Environment::DEVELOPMENT,              // The fallback environment type.
   *   platforms: [MyCustomPlatform::class],            // An array of additional platform classes to register.
   *   stacks: [MyCustomStack::class],                  // An array of additional stack classes to register.
   *   contexts: [MyCustomContext::class],              // An array of additional context classes to register.
   * );
   * @endcode
   *
   * @param bool $contextualize
   *   Whether to apply the context automatically when the environment type is
   *   requested. Set to FALSE to prevent automatic contextualization. In this
   *   case, call ::contextualize() manually to apply the context.
   * @param string $fallback
   *   The fallback environment type to use if the active platform is not able
   *   to determine the environment type. Default is Environment::DEVELOPMENT.
   * @param array<int,\DrevOps\EnvironmentDetector\Platforms\PlatformInterface> $platforms
   *   An array of additional platform classes to register.
   * @param array<int,\DrevOps\EnvironmentDetector\Stacks\StackInterface> $stacks
   *   An array of additional stack classes to register.
   * @param array<int,\DrevOps\EnvironmentDetector\Contexts\ContextInterface|class-string<\DrevOps\EnvironmentDetector\Contexts\ContextInterface>> $contexts
   *   An array of additional contexts or class names to register.
   */
  public static function init(
    bool $contextualize = TRUE,
    string $fallback = self::DEVELOPMENT,
    array $platforms = [],
    array $stacks = [],
    array $contexts = [],
  ): void {
    if (static::$isInitialized) {
      return;
    }

    static::$fallback = $fallback;

    static::collectPlatforms($platforms);
    static::collectStacks($stacks);
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
    static::$platform = NULL;
    static::$platforms = NULL;
    static::$stack = NULL;
    static::$stacks = NULL;
    static::$context = NULL;
    static::$contexts = NULL;
    static::$isInitialized = FALSE;

    // The container probe caches its result for the run; clearing it lets the
    // next detection re-probe, which a test simulating a different host relies
    // on.
    Container::resetCache();

    if ($all) {
      static::$fallback = self::DEVELOPMENT;
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
      $platform = static::getActivePlatform();

      if ($platform instanceof PlatformInterface) {
        // The platform owns the type; fall back only when it cannot name a
        // tier.
        $type = $platform->type() ?: static::$fallback;
      }
      else {
        // No platform matched: the run is local, or ci when a generic CI signal
        // is present.
        $type = getenv('CI') ? self::CI : self::LOCAL;
      }

      putenv('ENVIRONMENT_TYPE=' . $type);
    }

    return $type;
  }

  /**
   * Get the active platform.
   *
   * @return \DrevOps\EnvironmentDetector\Platforms\PlatformInterface|null
   *   The active platform or NULL if none is active.
   */
  public static function getActivePlatform(): ?PlatformInterface {
    if (!static::$platform instanceof PlatformInterface) {
      $active = NULL;

      // Ensure at most one active platform exists.
      foreach (static::collectPlatforms() as $platform) {
        if ($platform->active()) {
          if ($active !== NULL) {
            throw new \Exception('Multiple active environment platforms detected: ' . $active->id() . ' and ' . $platform->id());
          }
          $active = $platform;
        }
      }

      static::$platform = $active;
    }

    return static::$platform;
  }

  /**
   * Get the active stack.
   *
   * @return \DrevOps\EnvironmentDetector\Stacks\StackInterface|null
   *   The active stack or NULL if none is active.
   */
  public static function getActiveStack(): ?StackInterface {
    if (!static::$stack instanceof StackInterface) {
      // Registered most-specific first, so the first stack whose own signal
      // matches wins: a specific container, matched by the marker its tool
      // sets (DDEV, Lando); then the generic container, matched by probing for
      // containerisation; then the native host as the last-resort fallback.
      foreach (static::collectStacks() as $stack) {
        if ($stack->active()) {
          static::$stack = $stack;
          break;
        }
      }
    }

    return static::$stack;
  }

  /**
   * Get the list of registered platforms.
   *
   * @param array<int,\DrevOps\EnvironmentDetector\Platforms\PlatformInterface> $additional
   *   An array of additional platform classes to register.
   *
   * @return \DrevOps\EnvironmentDetector\Platforms\PlatformInterface[]
   *   An array of registered platforms.
   */
  protected static function collectPlatforms(array $additional = []): array {
    if (!static::$platforms) {
      static::$platforms = [];

      $instances = array_merge(self::PLATFORMS, $additional);

      foreach ($instances as $instance) {
        $instance = is_string($instance) ? new $instance() : $instance;

        if (!($instance instanceof PlatformInterface)) {
          throw new \InvalidArgumentException('The platform must implement PlatformInterface');
        }

        static::addPlatform($instance);
      }

      static::$platforms ??= [];
    }

    return static::$platforms;
  }

  /**
   * Add a custom platform.
   *
   * @param \DrevOps\EnvironmentDetector\Platforms\PlatformInterface $platform
   *   The platform to add.
   *
   * @throws \InvalidArgumentException
   *   If a platform with the same ID is already registered.
   */
  protected static function addPlatform(PlatformInterface $platform): void {
    if (array_key_exists($platform->id(), static::$platforms ?? [])) {
      throw new \InvalidArgumentException(sprintf('Platform with ID "%s" is already registered', $platform->id()));
    }

    static::$platforms[$platform->id()] = $platform;
    // Reset the active platform to make sure it is recalculated based on the
    // new platform.
    static::$platform = NULL;
  }

  /**
   * Get the list of registered stacks.
   *
   * @param array<int,\DrevOps\EnvironmentDetector\Stacks\StackInterface> $additional
   *   An array of additional stack classes to register.
   *
   * @return \DrevOps\EnvironmentDetector\Stacks\StackInterface[]
   *   An array of registered stacks.
   */
  protected static function collectStacks(array $additional = []): array {
    if (!static::$stacks) {
      static::$stacks = [];

      // The generic fallbacks are appended after the specific stacks and any
      // user additions so a more specific stack is always matched first: the
      // generic container yields to a specific container (DDEV, Lando, or a
      // supplied one), and the native host - which always matches - stays the
      // final fallback. A stack that extends one of these generics is therefore
      // always tried ahead of it.
      $instances = array_merge(self::STACKS, $additional, [Container::class, Native::class]);

      foreach ($instances as $instance) {
        $instance = is_string($instance) ? new $instance() : $instance;

        if (!($instance instanceof StackInterface)) {
          throw new \InvalidArgumentException('The stack must implement StackInterface');
        }

        static::addStack($instance);
      }

      static::$stacks ??= [];
    }

    return static::$stacks;
  }

  /**
   * Add a custom stack.
   *
   * @param \DrevOps\EnvironmentDetector\Stacks\StackInterface $stack
   *   The stack to add.
   *
   * @throws \InvalidArgumentException
   *   If a stack with the same ID is already registered.
   */
  protected static function addStack(StackInterface $stack): void {
    if (array_key_exists($stack->id(), static::$stacks ?? [])) {
      throw new \InvalidArgumentException(sprintf('Stack with ID "%s" is already registered', $stack->id()));
    }

    static::$stacks[$stack->id()] = $stack;
    // Reset the active stack to make sure it is recalculated based on the new
    // stack.
    static::$stack = NULL;
  }

  /**
   * Apply the active context.
   */
  protected static function applyActiveContext(): void {
    $context = static::getActiveContext();
    if ($context instanceof ContextInterface) {
      // Apply generic context changes.
      $context->contextualize();
      // Apply platform-specific context changes.
      static::getActivePlatform()?->contextualize($context);
      // Apply stack-specific context changes.
      static::getActiveStack()?->contextualize($context);
    }
  }

  /**
   * Get the active context.
   *
   * @return \DrevOps\EnvironmentDetector\Contexts\ContextInterface|null
   *   The active context or NULL if none is active.
   */
  public static function getActiveContext(): ?ContextInterface {
    if (!static::$context instanceof ContextInterface) {
      $active = NULL;

      // Ensure at most one active context exists.
      foreach (static::collectContexts() as $context) {
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
   * @param array<int, \DrevOps\EnvironmentDetector\Contexts\ContextInterface|class-string<\DrevOps\EnvironmentDetector\Contexts\ContextInterface>> $additional
   *   An array of additional contexts or class names to register.
   *
   * @return \DrevOps\EnvironmentDetector\Contexts\ContextInterface[]
   *   An array of registered contexts.
   */
  protected static function collectContexts(array $additional = []): array {
    if (!static::$contexts) {
      static::$contexts = [];

      // Resolve each additional context to an instance up front so its ID is
      // known before the built-ins are registered. An additional context -
      // passed as an instance or a class-string - overrides a built-in of the
      // same ID instead of colliding with it; the explicit one wins.
      $additional_instances = [];
      $override_ids = [];
      foreach ($additional as $instance) {
        $instance = is_string($instance) ? new $instance() : $instance;

        if (!($instance instanceof ContextInterface)) {
          throw new \InvalidArgumentException('The context must implement ContextInterface');
        }

        $additional_instances[] = $instance;
        $override_ids[$instance->id()] = TRUE;
      }

      // Register the built-ins, skipping any an additional context overrides.
      foreach (self::CONTEXTS as $class) {
        $instance = new $class();

        if (!isset($override_ids[$instance->id()])) {
          static::addContext($instance);
        }
      }

      // Two additional contexts sharing an ID remain a misconfiguration and
      // throw here.
      foreach ($additional_instances as $additional_instance) {
        static::addContext($additional_instance);
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
  protected function __construct() {
  }

  /**
   * Prevent cloning this class.
   */
  protected function __clone() {
  }
  // @codeCoverageIgnoreEnd
  // phpcs:enable DrupalPractice.Commenting.CommentEmptyLine.SpacingAfter
  // phpcs:enable Drupal.Commenting.FunctionComment.WrongStyle
  // phpcs:enable Squiz.WhiteSpace.FunctionSpacing.After

}
