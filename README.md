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

[![Vortex Ecosystem](https://img.shields.io/badge/%F0%9F%8C%80-Vortex%20Ecosystem-2C5A68?style=for-the-badge&labelColor=65ACBC)](https://github.com/drevops/vortex)
</div>

---

Answers one question, with no configuration: **what kind of environment is this code running in?** It detects a single environment type - `local`, `ci`, `development`, `preview`, `stage`, or `production` - from the hosting platform or CI platform the code runs on, and also recognises the local stack (native host or container) beneath it.

- Zero configuration: a single call detects and caches the type.
- Recognises hosting platforms (Acquia, Lagoon, Pantheon, Platform.sh, Skpr, Tugboat) and CI platforms (GitHub Actions, GitLab CI, CircleCI).
- Recognises the stack it runs on (native host, Container, DDEV, Lando).
- Turnkey Drupal integration: one line in `settings.php` detects the environment and owns all its settings.
- Extensible with custom platforms, stacks, and contexts, with a safe fallback type.

## Installation

```bash
composer require drevops/environment-detector
```

## Usage

The detector works in any PHP application, and ships a turnkey integration for Drupal.

### Drupal

A single line in `settings.php` detects the environment **and** applies every setting the detector owns - the resolved type, trusted hosts, reverse proxy, cache prefix, file paths, and more:

```php
require DRUPAL_ROOT . '/../vendor/drevops/environment-detector/environment.drupal.php';
```

That is the complete, recommended integration - nothing else is needed in `settings.php`. With it in place the detector:

- resolves the environment type and writes it to `$settings['environment']`;
- adds the universal loopback trusted hosts (`^localhost$`, `^127\.0\.0\.1$`);
- applies the active platform's Drupal settings (e.g. Lagoon reverse proxy and routes, Acquia config and temp paths);
- applies the active stack's Drupal settings (e.g. the container service-host allowlist and `LOCALDEV_URL`).

The file is `require`d rather than autoloaded so it runs in the caller's scope: it hands the site's own `$settings` and `$config` to the detector by reference, and the detector writes the settings Drupal reads back. (A library writing the global `$settings` would update a different variable than the local one Drupal core consumes, so the settings would silently never land.) See [Contexts](#contexts) to extend or override what is applied.

### Any PHP application

Detect the environment type directly through the static facade:

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

## Environment types

The built-in detectors resolve to one of these types (a custom platform can return its own, read via `Environment::is('custom-type')`):

| Type | What it is | Lifespan |
|------|-----------|----------|
| `local` | Your own machine or local stack (native host, DDEV, Lando, Docker); no hosting platform is active. | Persistent (developer-owned) |
| `ci` | An automated CI runner (GitHub Actions, GitLab CI, CircleCI). | Ephemeral, per job |
| `development` | A shared, long-lived hosting environment for ongoing integration work. Also the safe [fallback](#configuration). | Persistent |
| `preview` | A short-lived, throwaway per-branch or per-PR environment with its own fully-built site on its own standalone URL. | Ephemeral |
| `stage` | A persistent pre-production environment that mirrors production; used for UAT, QA, and release sign-off. | Persistent |
| `production` | The live environment serving real users. | Persistent |

`preview` is the only ephemeral, per-change tier with its own URL - which is what sets it apart from `development` (shared and long-lived) and `stage` (a persistent pre-production mirror).

## How it works

A run is a set of nested rings, from the outermost ring inward to the application:

```text
┌─ PLATFORM ── hosting (tiered) · CI (flat) · none ⇒ local ───────────────┐
│   ┌─ STACK ── native · container · ddev · lando ────────────────────┐   │
│   │   ┌─ RUNTIME ── PHP 8.x ────────────────────────────────────┐   │   │
│   │   │   ┌─ APP / CONTEXT ── Drupal ───────────────────────┐   │   │   │
│   │   │   └─────────────────────────────────────────────────┘   │   │   │
│   │   └─────────────────────────────────────────────────────────┘   │   │
│   └─────────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────────┘
```

- **Platform** - the outermost ring, and the *only* one that decides the type. A hosting platform maps to `production`/`stage`/`development`/`preview`; a CI platform maps to `ci`; with no platform at all the type is `local` (or `ci` when a generic `CI` signal is present).
- **Stack** - the substrate the run sits in (`native`, `container`, `ddev`, `lando`). A stack nests inside a platform, never decides the type, and can only update the context. `ddev` and `lando` are specific containers, `container` is the generic container fallback, and `native` is the native host (bare metal), the fallback when nothing else matches.
- **Context** - the application/framework (e.g. Drupal, WordPress, Laravel) that detected settings are applied to.
- **Runtime** (PHP) is shown only to complete the picture; it is not detected.

Two rules follow:

1. **At most one platform is active.** Two active platforms (say Acquia *and* Lagoon) is a genuine misconfiguration and throws.
2. **Exactly one stack is always active - the most specific stack that matches, or the native host as the fallback.** A container inside Acquia or inside CI is just an inner ring; it never collides with the platform. The most specific match wins - DDEV over the generic `container`, say - and the native host is the last-resort fallback when nothing else matches.

When a context is active, it applies its own generic changes first, then the active platform and the active stack apply their own on top. This happens even when the type was pre-set via `ENVIRONMENT_TYPE`.

## Configuration

`init()` is optional - the `is*()` methods initialise on first use. Call it directly only to register custom detectors or change the fallback:

```php
Environment::init(
  contextualize: TRUE,                 // Apply context settings automatically (default).
  fallback: Environment::DEVELOPMENT,  // Type used when a platform cannot resolve its tier.
  platforms: [new MyHostingPlatform()],
  stacks: [new MyStack()],
  contexts: [new MyContext()],
);
```

The fallback (`development` by default) applies only when a platform is active but cannot resolve its tier - it is never used to silently downgrade a known environment. It guards against applying local settings in production, or production settings locally.

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

### How platforms map to types

Each hosting platform maps its own signal to a type. `preview` is the catch-all: any environment a platform spins up that it cannot place in one of the three persistent tiers (`production`, `stage`, `development`) is treated as an ephemeral, per-branch or per-PR build.

The name-based platforms (Acquia, Pantheon, Skpr) read an environment **name** - recognised names map to a persistent tier, and any other name is a `preview`. The branch-based platforms (Lagoon, Platform.sh) type an environment as production or non-production, then resolve the exact tier from the deployed Git branch: the `develop` branch is `development`, and any other non-production, non-stage branch is a `preview`.

| Platform | Signal | `production` | `stage` | `development` | `preview` |
|----------|--------|--------------|---------|---------------|-----------|
| Acquia | `AH_SITE_ENVIRONMENT` | `prod` | `stage`, `test` | `dev` | any other name (e.g. `ode*` on-demand) |
| Pantheon | `PANTHEON_ENVIRONMENT` | `live` | `test` | `dev` | any other name (multidev) |
| Skpr | `SKPR_ENV` | `prod` | `stg` | `dev` | any other name |
| Lagoon | `LAGOON_ENVIRONMENT_TYPE` | env-type `production`, or the `ENVIRONMENT_PRODUCTION_BRANCH` branch | `main`/`master`, `release/*`, `hotfix/*` | `develop` branch | env-type `development` on any other branch |
| Platform.sh | `PLATFORM_ENVIRONMENT_TYPE` | type `production` | type `staging` | type `development` on the `develop` branch | type `development` on any other branch |
| Tugboat | `TUGBOAT_PREVIEW_ID` | - | - | - | always |

On Lagoon, `main`/`master` resolve to `stage` unless one of them is named by `ENVIRONMENT_PRODUCTION_BRANCH`, in which case it is `production`. The branch names (`main`, `master`, `release/*`, `hotfix/*`, `develop`) are built-in conventions.

Every built-in platform resolves to one of these tiers whenever it is active - an active environment it cannot place in a persistent tier (an unrecognised name or env-type) is a `preview`. The `development` [fallback](#configuration) applies only to custom platforms that return no type. The CI platforms - CircleCI, GitHub Actions, GitLab CI - always resolve to `ci`.

Read the active platform:

```php
Environment::init();

if (Environment::getActivePlatform()?->id() === 'acquia') {
  // Acquia-specific logic.
}
```

Add your own by extending `AbstractPlatform`. Implement a context's capability interface (`DrupalContextualizerInterface` for Drupal) to apply settings through a typed method the detector resolves for you; override `contextualize()` to handle a context defined at runtime instead:

```php
use DrevOps\EnvironmentDetector\Contexts\Drupal;
use DrevOps\EnvironmentDetector\Contexts\DrupalContextualizerInterface;
use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Platforms\AbstractPlatform;

class CustomHosting extends AbstractPlatform implements DrupalContextualizerInterface {
  public const ID = 'customhosting';

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

  // Resolved automatically for the Drupal context. To handle a context defined
  // at runtime, override contextualize(ContextInterface $context) instead.
  public function contextualizeDrupal(Drupal $context): void {
    $context->settings['some_setting'] = 'value';
  }
}

Environment::init(platforms: [new CustomHosting()]);
```

## Stacks

A stack is the substrate the run sits in. Stacks never decide the type. Exactly one stack is always active - the most specific stack that matches, or the native host as the last-resort fallback. `Container` is the generic container fallback, matched by probing for containerisation; `Ddev` and `Lando` are specific containers, matched by the marker their tool sets (`IS_DDEV_PROJECT`, `LANDO_INFO`); and `Native` is the native host, used when nothing else matches. Built-ins:

- [Container](src/Stacks/Container.php)
- [DDEV](src/Stacks/Ddev.php)
- [Lando](src/Stacks/Lando.php)
- [Native](src/Stacks/Native.php)

Read the active stack:

```php
Environment::init();

if (Environment::getActiveStack()?->id() === 'ddev') {
  // DDEV-specific logic.
}
```

`getActiveStack()` returns the first registered stack whose `active()` matches - your custom stacks included - with the `native` host as the last-resort fallback.

Add your own by extending `AbstractStack`. As with platforms, implement a context's capability interface for the typed path, or override `contextualize()` for a runtime context:

```php
use DrevOps\EnvironmentDetector\Contexts\Drupal;
use DrevOps\EnvironmentDetector\Contexts\DrupalContextualizerInterface;
use DrevOps\EnvironmentDetector\Stacks\AbstractStack;

class CustomStack extends AbstractStack implements DrupalContextualizerInterface {
  public const ID = 'customstack';

  public function active(): bool {
    return getenv('CUSTOM_STACK') !== false;
  }

  // Resolved automatically for the Drupal context. To handle a context defined
  // at runtime, override contextualize(ContextInterface $context) instead.
  public function contextualizeDrupal(Drupal $context): void {
    $context->settings['some_setting'] = 'value';
  }
}

Environment::init(stacks: [new CustomStack()]);
```

## Contexts

A context is the framework or application that detected settings are applied to. Once a context is active it applies its own generic changes first, then the active platform and the active stack layer their changes on top of the same target (the Lagoon platform, say, adds its reverse-proxy and trusted-host settings).

A platform or stack applies settings to a context in one of two ways. For a context the library ships, it implements that context's capability interface - [`DrupalContextualizerInterface`](src/Contexts/DrupalContextualizerInterface.php) for Drupal - and the detector resolves the typed `contextualizeDrupal()` method automatically. For a context defined at runtime, it overrides `contextualize(ContextInterface $context)` and handles the context itself. The built-in platforms and stacks use the typed path.

The built-in [Drupal](src/Contexts/Drupal.php) context is the turnkey example: it holds the site's `$settings` and `$config` by reference and is wired up by the one-line [Drupal integration](#drupal) shown above.

> [!NOTE]
> Drupal is the only built-in context today, but the ring model is framework-agnostic. We are looking to add more framework integrations - contributions that add contexts for other frameworks (WordPress, Laravel, Symfony, and more) are welcome. Open an issue or a pull request.

### Custom contexts

Add your own by implementing `ContextInterface`. Bind the framework's own state by reference in the constructor, then write to it in `contextualize()` - the same by-reference approach the built-in Drupal context uses:

```php
use DrevOps\EnvironmentDetector\Contexts\ContextInterface;

class CustomContext implements ContextInterface {
  // Hold the framework's own config by reference so the changes land in the
  // array the framework reads back, not a separate copy.
  public array $config;

  public function __construct(array &$config = []) {
    $this->config = &$config;
  }

  public function id(): string {
    return 'myframework';
  }

  public function active(): bool {
    return class_exists('MyFramework');
  }

  public function contextualize(): void {
    $this->config['custom_value'] = $_SERVER['custom_value'] ?? 'default';
  }
}

// $config is the framework's own configuration array, in scope here.
Environment::init(contexts: [new CustomContext($config)]);
```

## Environment variables

Beyond the platform and stack detection signals the hosting or CI provider sets (listed in the [Platforms](#platforms) table), the detector reads a few variables that **you** set - to override detection or to shape the settings a context applies. All are optional.

| Variable | Effect | Applies to | When unset |
|----------|--------|------------|------------|
| `ENVIRONMENT_TYPE` | If set before detection, the value is used verbatim and overrides all detection (handy for forcing a type while debugging); the resolved type is written back here either way. | Core | Detection runs and populates it. |
| `CI` | When no platform is active, a truthy value resolves the type to `ci` instead of `local`. Most CI providers set it automatically. | Core | Treated as not-CI, so `local`. |
| `ENVIRONMENT_PRODUCTION_BRANCH` | Names the Git branch deployed as production: a deployed branch equal to it resolves to `production`, and it also forms the Drupal `cache_prefix`. | Lagoon | Branches are typed by built-in conventions only. |
| `DRUPAL_CONFIG_PATH` | Sets the Drupal `config_sync_directory`. | Acquia | Falls back to the Acquia-provided `config_vcs_directory`. |
| `DRUPAL_TMP_PATH` | Sets the Drupal `file_temp_path` explicitly; takes precedence over the shared path below. | Acquia | `/tmp`, or the shared path when `DRUPAL_TMP_PATH_IS_SHARED` is set. |
| `DRUPAL_TMP_PATH_IS_SHARED` | When truthy, points `file_temp_path` at the shared GFS mount (`/mnt/gfs/<group>.<env>/tmp`). | Acquia | `file_temp_path` stays `/tmp`. |
| `DRUPAL_ACQUIA_SETTINGS_FILE` | Overrides the path to the Acquia-provided `*-settings.inc` file that is included. | Acquia | `/var/www/site-php/<group>/<group>-settings.inc`. |
| `LOCALDEV_URL` | The site's local development URL, added as a Drupal `trusted_host_patterns` entry (the scheme is stripped). | Container stack | Only the built-in service-host allowlist (`web`, `app`, `webserver`, `nginx`, `apache`, `apache2`) is added. |
| `SERVICE_HOSTS` | Comma-separated internal service hostnames merged with the built-in container allowlist into a single Drupal `trusted_host_patterns` alternation regex (entries are trimmed, blanks dropped, and regex metacharacters escaped). | Container stack | Only the built-in service-host allowlist (`web`, `app`, `webserver`, `nginx`, `apache`, `apache2`) is added. |

The `DRUPAL_*` variables take effect only when the [Drupal](#contexts) context is active.

## Maintenance

```bash
composer install
composer lint
composer test
```

---

*This repository was created using the *[*Scaffold*](https://getscaffold.dev/)*
project template.*
