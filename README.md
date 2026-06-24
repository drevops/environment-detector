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

Answers one question, with no configuration: **what kind of environment is this code running in?** It maps the host you are on to a single environment type - `local`, `ci`, `development`, `preview`, `stage`, or `production` - across common hosting providers, CI services, and local stacks.

- Zero configuration: a single call detects and caches the type.
- Recognises hosting platforms (Acquia, Lagoon, Pantheon, Platform.sh, Skpr, Tugboat) and CI services (GitHub Actions, GitLab CI, CircleCI).
- Recognises local stacks (Container, DDEV, Lando).
- Applies framework-specific settings through contexts (Drupal).
- Extendable with custom platforms, stacks, and contexts, with a safe fallback type.

## Installation

```bash
composer require drevops/environment-detector
```

## Quick start

```php
use DrevOps\EnvironmentDetector\Environment;

if (Environment::isProd()) {
  // Apply production settings.
}
```

The first call auto-detects and caches the result. The full set is `isLocal()`, `isCi()`, `isDev()`, `isPreview()`, `isStage()`, `isProd()`, plus `Environment::is('custom-type')` for custom types.

The detected type is also written to the `ENVIRONMENT_TYPE` environment variable:

```php
Environment::init();
if (getenv('ENVIRONMENT_TYPE') === Environment::PRODUCTION) {
  // ...
}
```

If `ENVIRONMENT_TYPE` is already set, that value wins - handy for forcing a type while debugging.

## How it works

A run is a set of nested rings, from an outer context down to the application:

```text
┌─ PLATFORM ── hosting (tiered) · CI (flat) · none ⇒ local ───────────────┐
│   ┌─ STACK ── host · container · ddev · lando ──────────────────────┐   │
│   │   ┌─ RUNTIME ── PHP 8.x ────────────────────────────────────┐   │   │
│   │   │   ┌─ APP / CONTEXT ── Drupal ───────────────────────┐   │   │   │
│   │   │   └─────────────────────────────────────────────────┘   │   │   │
│   │   └─────────────────────────────────────────────────────────┘   │   │
│   └─────────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────────┘
```

- **Platform** - the outermost ring, and the *only* one that decides the type. A hosting platform maps to `production`/`stage`/`development`/`preview`; a CI platform maps to `ci`; with no platform at all the type is `local` (or `ci` when a generic `CI` signal is present).
- **Stack** - the substrate the run sits in (`container`, `ddev`, `lando`). A stack nests inside a platform, never decides the type, and only contributes settings. `ddev` and `lando` are specific containers; `container` is the generic fallback.
- **Context** - the application/framework (e.g. Drupal) that detected settings are applied to.
- **Runtime** (PHP) is shown only to complete the picture; it is not detected.

Two rules follow:

1. **At most one platform is active.** Two active platforms (say Acquia *and* Lagoon) is a genuine misconfiguration and throws.
2. **Exactly one stack is active (the most specific container), or none on bare metal.** A container inside Acquia, or inside CI, is just an inner ring - it never collides with the platform. The most specific container that matches wins (DDEV over the generic container, say); the generic `container` is the fallback.

When a context is active, it applies its generic settings first, then the active platform and the active stack apply their own on top. This happens even when the type was pre-set via `ENVIRONMENT_TYPE`.

## Configuration

`init()` is optional - the `is*()` methods initialise on first use. Call it directly only to register custom detectors or change the fallback:

```php
Environment::init(
  contextualize: TRUE,                 // Apply context settings automatically (default).
  fallback: Environment::DEVELOPMENT,  // Type used when a platform cannot name its tier.
  platforms: [new MyHostingPlatform()],
  stacks: [new MyStack()],
  contexts: [new MyContext()],
);
```

The fallback (`development` by default) applies only when a platform is active but cannot resolve a tier - it is never used to silently downgrade a known environment. It guards against applying local settings in production, or production settings locally.

## Platforms

A platform is the outermost ring and the only one that decides the type. Built-ins:

- [Acquia](src/Platforms/Acquia.php)
- [CircleCI](src/Platforms/CircleCi.php)
- [GitHub Actions](src/Platforms/GitHubActions.php)
- [GitLab CI](src/Platforms/GitLabCi.php)
- [Lagoon](src/Platforms/Lagoon.php)
- [Pantheon](src/Platforms/Pantheon.php)
- [Platform.sh](src/Platforms/PlatformSh.php)
- [Skpr](src/Platforms/Skpr.php)
- [Tugboat](src/Platforms/Tugboat.php)

Read the active platform:

```php
Environment::init();

if (Environment::getActivePlatform()?->id() === 'acquia') {
  // Acquia-specific logic.
}
```

Add your own by implementing `PlatformInterface`:

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
    // Optional: apply platform-specific context changes.
  }
}

Environment::init(platforms: [new CustomHosting()]);
```

## Stacks

A stack is the substrate the run sits in. Stacks never decide the type. Exactly one stack is active - the most specific container that matches, or none on bare metal. `Container` is the generic fallback; `Ddev` and `Lando` are specific containers that match only when they are also a container. Built-ins:

- [Container](src/Stacks/Container.php)
- [DDEV](src/Stacks/Ddev.php)
- [Lando](src/Stacks/Lando.php)

Read the active stack:

```php
Environment::init();

if (Environment::getActiveStack()?->id() === 'ddev') {
  // DDEV-specific logic.
}
```

`getActiveStack()` returns the single active stack, or `null` on bare metal.

Add your own by implementing `StackInterface`:

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
    // Optional: apply stack-specific context changes.
  }
}

Environment::init(stacks: [new CustomStack()]);
```

## Contexts

A context is the framework or application that detected settings are applied to. The [Drupal](src/Contexts/Drupal.php) context, for example, writes to the global `$settings` array; the active platform and the active stack then layer their own changes on top (the Lagoon platform, say, adds its reverse-proxy and trusted-host settings).

Add your own by implementing `ContextInterface`:

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
    global $configuration;
    $configuration['custom_value'] = $_SERVER['custom_value'] ?? 'default';
  }
}

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
