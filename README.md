<p align="center">
  <a href="" rel="noopener">
  <img width=100px height=100px src="logo.png" alt="Environment Detector"></a>
</p>

<h1 align="center">Zero-config environment type detection</h1>

<div align="center">

[![GitHub Issues](https://img.shields.io/github/issues/drevops/environment-detector.svg)](https://github.com/drevops/environment-detector/issues)
[![GitHub Pull Requests](https://img.shields.io/github/issues-pr/drevops/environment-detector.svg)](https://github.com/drevops/environment-detector/pulls)
[![Test PHP](https://github.com/drevops/environment-detector/actions/workflows/test-php.yml/badge.svg)](https://github.com/drevops/environment-detector/actions/workflows/test-php.yml)
[![codecov](https://codecov.io/gh/drevops/environment-detector/graph/badge.svg?token=Q2S80GFSF6)](https://codecov.io/gh/drevops/environment-detector)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/drevops/environment-detector)
![LICENSE](https://img.shields.io/github/license/drevops/environment-detector)
![Renovate](https://img.shields.io/badge/renovate-enabled-green?logo=renovatebot)

</div>

---

## Features

- Detects environment type: `local`, `ci`, `dev`, `preview`, `stage`, `prod`, or user-defined
- Supports many popular hosting and CI platforms out-of-the-box: [Acquia](src/Platforms/Acquia.php), [CircleCI](src/Platforms/CircleCi.php), [GitHub Actions](src/Platforms/GitHubActions.php), [GitLab CI](src/Platforms/GitLabCi.php), [Lagoon](src/Platforms/Lagoon.php), [Pantheon](src/Platforms/Pantheon.php), [Platform.sh](src/Platforms/PlatformSh.php), [Skpr](src/Platforms/Skpr.php), [Tugboat](src/Platforms/Tugboat.php)
- Detects development stacks: [Docker](src/Stacks/Docker.php), [DDEV](src/Stacks/Ddev.php), [Lando](src/Stacks/Lando.php)
- Detects application contexts: [Drupal](src/Contexts/Drupal.php) (more to come)
- Simple API for checking current environment
- Extendable via custom platforms, stacks and contexts
- Fallback support for precise control
- Optimised for performance with static caching

## Installation

```bash
composer require drevops/environment-detector
```

## Quick Start

```php
use DrevOps\EnvironmentDetector\Environment;

Environment::init();
if (getenv('ENVIRONMENT_TYPE') === Environment::LOCAL) {
  // Apply local settings.
}
```

Alternatively, use the convenience methods:

```php
use DrevOps\EnvironmentDetector\Environment;

// No need to init() - a first call to is*() will auto-initialize.
if (Environment::isLocal()) {
  // Apply local settings.
}

if (Environment::isProd()) {
  // Apply production settings.
}
```

## Architecture

Detection is modelled as nested rings. A run wraps from an outer ring down to
the application:

```
┌─ PLATFORM ── hosting (tiered) · CI (flat) · none ⇒ local ───────────────┐
│   ┌─ STACK ── host · docker · ddev · lando ─────────────────────────┐   │
│   │   ┌─ RUNTIME ── PHP 8.x ────────────────────────────────────┐   │   │
│   │   │   ┌─ APP / CONTEXT ── Drupal ───────────────────────┐   │   │   │
│   │   │   └─────────────────────────────────────────────────┘   │   │   │
│   │   └─────────────────────────────────────────────────────────┘   │   │
│   └─────────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────────┘
```

- **Platform** is the outermost ring and the only one that decides the
  environment type: hosting maps to `production`/`stage`/`development`/`preview`,
  CI maps to `ci`, and no platform at all means `local`.
- **Stack** is the substrate the platform runs in (`docker`/`ddev`/`lando`). It
  nests inside a platform and never decides the type - it only contributes
  settings.
- **Context** is the app/framework (e.g. Drupal) where settings are applied.
- **Runtime** (PHP) is shown only to complete the picture; it is not modelled.

Two rules follow from this:

1. **Exactly one platform is active at a time.** Two active platforms (for
   example Acquia *and* Lagoon) is a real misconfiguration and throws.
2. **Stacks may co-exist.** Docker inside Acquia, or Lando inside CI, are just
   inner rings - they never collide with the platform and simply contribute
   their settings.

### How detection works

1. **Platform detection:** Each platform checks for environment-specific
   variables or files to identify itself. The single active platform maps its
   internal state to a type like `dev`, `prod`, or a custom type. With no active
   platform, the type is `ci` when a generic `CI` signal is present, otherwise
   `local`.
2. **Stack detection:** Every active stack is detected independently of the
   platform.
3. **Contextualization:** Optionally applies framework-specific changes. The
   active context applies its generic changes first (e.g. modify the Drupal
   `$settings` global to add `$settings['environment']`), then the active
   platform and every active stack apply their own context-specific changes.

The resolved type is stored in the `ENVIRONMENT_TYPE` env var. If already set,
this value takes precedence over detection. The `contextualize` step still
applies context changes even if the type is pre-set via environment variable.

## Advanced Usage

### Advanced initialization with customization

```php
Environment::init(
  contextualize: TRUE,                            // Whether to apply the context automatically
  fallback: Environment::DEVELOPMENT,             // The fallback environment type
  platforms: [new MyCustomPlatform()],            // Additional platform instances
  stacks: [new MyCustomStack()],                  // Additional stack instances
  contexts: [new MyCustomContext()],              // Additional context instances
);
```

### Fallback Type

If an active platform cannot determine the environment type, a fallback
`Environment::DEVELOPMENT` will be returned by default. This is to ensure that, in case of
misconfiguration, the application does not apply local settings in production or production
settings in local - 'development' type is the safest default.

You can set a different fallback type during initialization:

```php
Environment::init(fallback: Environment::PRODUCTION);
```

## Platforms

A platform is the outermost ring and the only one that decides the environment
type. Exactly one platform can be active at a time - if two match, an exception
is thrown (a real misconfiguration). If none match, the type is `ci` when a
generic `CI` signal is present, otherwise `local`. Register custom platforms
using `init(platforms: [new MyCustomPlatform()])`.

Supported built-ins:

- [Acquia](src/Platforms/Acquia.php)
- [CircleCI](src/Platforms/CircleCi.php)
- [GitHub Actions](src/Platforms/GitHubActions.php)
- [GitLab CI](src/Platforms/GitLabCi.php)
- [Lagoon](src/Platforms/Lagoon.php)
- [Pantheon](src/Platforms/Pantheon.php)
- [Platform.sh](src/Platforms/PlatformSh.php)
- [Skpr](src/Platforms/Skpr.php)
- [Tugboat](src/Platforms/Tugboat.php)

### Accessing the active platform

```php
// Initialize first to detect the active platform.
Environment::init();

$platform = Environment::getActivePlatform();
if ($platform && $platform->id() === 'acquia') {
  // Acquia-specific logic.
}
```

### Adding a custom platform

```php
use DrevOps\EnvironmentDetector\Contexts\ContextInterface;
use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Platforms\PlatformInterface;

class CustomHosting implements PlatformInterface {
  public function id(): string {
    return 'customhosting';
  }

  public function active(): bool {
    return isset($_SERVER['CUSTOM_ENV']);
  }

  public function type(): ?string {
    return match ($_SERVER['CUSTOM_ENV_TYPE'] ?? null) {
      'dev' => Environment::DEVELOPMENT,
      'qa' => 'qa',
      'live' => Environment::PRODUCTION,
      default => null,
    };
  }

  public function contextualize(ContextInterface $context): void {
    // Optional: Apply platform-specific context changes.
  }
}

// Register the custom platform during initialization.
Environment::init(platforms: [new CustomHosting()]);
```

## Stacks

A stack is the substrate the platform runs in. Stacks never carry the environment
type and may co-exist - Docker inside Acquia or Lando inside CI are just inner
rings. Each active stack may contribute settings to the active context. Register
custom stacks using `init(stacks: [new MyCustomStack()])`.

Supported built-ins:

- [Docker](src/Stacks/Docker.php)
- [DDEV](src/Stacks/Ddev.php)
- [Lando](src/Stacks/Lando.php)

### Accessing active stacks

```php
// Initialize first to detect the active stacks.
Environment::init();

foreach (Environment::getActiveStacks() as $stack) {
  if ($stack->id() === 'ddev') {
    // DDEV-specific logic.
  }
}
```

### Adding a custom stack

```php
use DrevOps\EnvironmentDetector\Contexts\ContextInterface;
use DrevOps\EnvironmentDetector\Stacks\StackInterface;

class CustomStack implements StackInterface {
  public function id(): string {
    return 'customstack';
  }

  public function active(): bool {
    return getenv('CUSTOM_STACK') !== false;
  }

  public function contextualize(ContextInterface $context): void {
    // Optional: Apply stack-specific context changes.
  }
}

// Register the custom stack during initialization.
Environment::init(stacks: [new CustomStack()]);
```

## Contexts

Contexts apply environment-specific changes to frameworks or applications. A context
applies generic changes to the application; the active platform and every active stack may
then apply their own context-specific changes.

For example, a **Drupal** context applies changes to the global `$settings` array, while
the **Lagoon** platform's `contextualize()` method adds Lagoon-specific changes to the same
array.

The goal is to have enough context changes to cover the most common use cases, but also
to allow adding custom contexts to cover specific use cases within the application.

### Adding a custom context

```php
use DrevOps\EnvironmentDetector\Contexts\ContextInterface;

class CustomContext implements ContextInterface {
  public function id(): string {
    return 'myframework';
  }

  public function active(): bool {
    return class_exists('MyFramework');
  }

  public function contextualize(): void {
    // Apply generic context changes.
    global $configuration;
    $configuration['custom_value'] = $_SERVER['custom_value'] ?? 'default';
  }
}

// Register the custom context during initialization.
Environment::init(contexts: [new CustomContext()]);
```

## Maintenance

```bash
composer install
composer lint
composer test
```

---

*This repository was created using the *[*Scaffold*](https://getscaffold.dev/)*
project template.*
