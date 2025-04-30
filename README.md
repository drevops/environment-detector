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

- Detects environment type: `local`, `ci`, `dev`, `preview`, `stage`, `prod`, or custom
- Supports many popular providers out-of-the-box
- Detects custom contexts: [Drupal](src/Contexts/Drupal.php) (more to come)
- Simple API for checking current environment
- Extendable via custom providers and contexts
- Override and fallback support for precise control

## Installation

```bash
composer require drevops/environment-detector
```

## Quick Start

```php
use DrevOps\EnvironmentDetector\Environment;

Environment::init();

// Using a pre-populated ENVIRONMENT_TYPE variable.
if (getenv('ENVIRONMENT_TYPE') === Environment::LOCAL) {
  // Local logic.
}

// Using a is* shorthand method.
if (Environment::isProd()) {
  // Production logic.
}

```

## How It Works

1. **Provider detection:** Each provider checks for environment-specific variables
   or files to identify itself.
2. **Type mapping:** Once identified, the provider maps its internal state to a
   type like `dev`, `prod`, or a custom type.
3. **Context detection**: Optionally applies provider- or framework-specific
   changes (e.g., modify Drupal `$settings` global variable).

The resolved type is stored in the `ENVIRONMENT_TYPE` env var. If already set,
this value is used directly.

## Providers

Only one provider can be active. If multiple match, or none match, an exception
is thrown. Register custom providers using `addProvider()`.

Supported built-ins:

- [Acquia](src/Providers/Acquia.php)
- [CircleCI](src/Providers/CircleCi.php)
- [DDEV](src/Providers/Ddev.php)
- [Docker](src/Providers/Docker.php)
- [GitHub Actions](src/Providers/GitHubActions.php)
- [GitLab CI](src/Providers/GitLabCi.php)
- [Lagoon](src/Providers/Lagoon.php)
- [Lando](src/Providers/Lando.php)
- [Pantheon](src/Providers/Pantheon.php)
- [Platform.sh](src/Providers/PlatformSh.php)
- [Tugboat](src/Providers/Tugboat.php)

## Advanced Usage

### Override Detected Type

You can override the resolved type based on the active provider and its returned
type. You may return an existing or custom type.

The advantage of this approach is that the provider resolution logic, and it's
type are encapsulated within the provider itself, while the resulting type
can be contextually overridden based on the discovered values.

```php
use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Provider;

Environment::setOverride(function (Provider $provider, ?string $current): ?string {
  if ($current === Environment::DEVELOPMENT && $provider->id() === 'tugboat') {
    return 'qa';
  }
  return $current;
});
```

### Fallback Type

If an environment type is not detected, a fallback `Environment::DEV` will be
returned by default.

You can set a different default type using the `setFallback()` method.

```php
Environment::setFallback(Environment::DEV);
```

### Accessing Provider Data

```php
if (Environment::provider()->id() === 'acquia') {
  // Acquia-specific logic.
}

if (Environment::provider()->data()['HOSTING_BRANCH'] === 'main') {
  // Main branch logic.
}
```

### Adding a Custom Provider

```php
use DrevOps\EnvironmentDetector\Provider;
use DrevOps\EnvironmentDetector\Environment;

class CustomHosting implements Provider {
  public function active(): bool {
    return isset($_SERVER['CUSTOM_ENV']);
  }

  public function data(): array {
    return ['CUSTOM_ENV' => $_SERVER['CUSTOM_ENV'] ?? null];
  }

  public function type(): ?string {
    return match ($_SERVER['CUSTOM_ENV_TYPE'] ?? null) {
      'dev' => Environment::DEVELOPMENT,
      'qa' => 'qa',
      'live' => Environment::PRODUCTION,
      default => null,
    };
  }

  public function id(): string {
    return 'customhosting';
  }

  public function label(): string {
    return 'Custom Hosting';
  }
}

Environment::addProvider(new CustomHosting());
```

### Contexts

Apply generic changes to the runtime environment.

For example, setting a configuration value based on the active
provider: a provider may require certain configurations to be present in the
application which are not specific to an application's implementation.

For example, a provider might require certain configuration values to exist (
e.g., setting a CORS policy based on the active environment URL). These
configurations are not specific to the application but are necessary for the
application to function properly in this provider's environment.

More specific or application-related settings should be handled in the
application’s own configuration, outside of the context.

#### Adding a custom context

You can use built-in or register your own with `addContext()`.

```php
use DrevOps\EnvironmentDetector\Context;

class CustomContext implements Context {
  // Add activation logic. Only one context can be active at a time.
  public function active(): bool {
    return isset($_SERVER['CUSTOM_CONTEXT']);
  }

  // Apply changes to the runtime environment.
  public function contextualize(): void {
    global $configuration;
    $configuration['custom_value'] = $_SERVER['custom_value'];
  }
}
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

