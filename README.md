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
- Supports many popular providers out-of-the-box: [Acquia](src/Providers/Acquia.php), [CircleCI](src/Providers/CircleCi.php), [DDEV](src/Providers/Ddev.php), [Docker](src/Providers/Docker.php), [GitHub Actions](src/Providers/GitHubActions.php), [GitLab CI](src/Providers/GitLabCi.php), [Lagoon](src/Providers/Lagoon.php), [Lando](src/Providers/Lando.php), [Pantheon](src/Providers/Pantheon.php), [Platform.sh](src/Providers/PlatformSh.php), [Skpr](src/Providers/Skpr.php), [Tugboat](src/Providers/Tugboat.php)
- Detects custom contexts: [Drupal](src/Contexts/Drupal.php) (more to come)
- Simple API for checking current environment
- Extendable via custom providers and contexts
- Override and fallback support for precise control
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

## How It Works

1. **Provider detection:** Each provider checks for environment-specific variables
   or files to identify itself.
2. **Type mapping:** Once identified, the provider maps its internal state to a
   type like `dev`, `prod`, or a custom type.
3. **Context detection**: Optionally applies provider- or framework-specific
   changes (e.g., modify Drupal `$settings` global variable to add `$settings['environment']` value).

The resolved type is stored in the `ENVIRONMENT_TYPE` env var. If already set,
this value takes precedence over the provider detection. The `contextualize`
still applies context changes even if the type is pre-set via environment
variable.

## Advanced Usage

### Advanced initialization with customization

```php
Environment::init(
  contextualize: TRUE,                            // Whether to apply the context automatically
  fallback: Environment::DEVELOPMENT,             // The fallback environment type
  override: function($provider, $type) {          // The override callback to change the environment type
    if ($type === Environment::DEVELOPMENT && $provider->id() === 'tugboat') {
      return 'qa';
    }
    return $type;
  },
  providers: [new MyCustomProvider()],            // Additional provider instances
  contexts: [new MyCustomContext()],              // Additional context instances
);
```

### Fallback Type

If an environment type is not detected, a fallback `Environment::DEVELOPMENT` will be
returned by default. This is to ensure that, in case of misconfiguration, the application
does not apply local settings in production or production settings in local - 'development'
type is the safest default.

You can set a different fallback type during initialization:

```php
Environment::init(fallback: Environment::PRODUCTION);
```

## Providers

Only one provider can be active. If multiple match, or none match, an exception
is thrown. Register custom providers using `init(providers:[MyCustomProvider::class])`.

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
- [Skpr](src/Providers/Skpr.php)
- [Tugboat](src/Providers/Tugboat.php)

### Accessing Provider Data

```php
// Initialize first to detect the active provider
Environment::init();

$provider = Environment::getActiveProvider();
if ($provider && $provider->id() === 'acquia') {
  // Acquia-specific logic
  $data = $provider->data();
  if (isset($data['AH_SITE_GROUP'])) {
    // Use Acquia-specific environment data
  }
}
```

### Adding a Custom Provider

```php
use DrevOps\EnvironmentDetector\Providers\ProviderInterface;
use DrevOps\EnvironmentDetector\Environment;

class CustomHosting implements ProviderInterface {
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

  public function contextualize(\DrevOps\EnvironmentDetector\Contexts\ContextInterface $context): void {
    // Optional: Apply provider-specific context changes
  }
}

// Register the custom provider during initialization
Environment::init(providers: [new CustomHosting()]);
```

### Contexts

Contexts apply environment-specific changes to frameworks or applications. A context may
provide generic changes that are applied to the application. A provider may also provide
provider-specific context changes.

For example, a **Drupal** context applies changes to the global `$settings` array, while a
**Lagoon** provider's `contextualize()` method adds Lagoon-specific changes to the `$settings` array.

The goal is to have enough context changes to cover the most common use cases, but also
to allow adding custom contexts to cover specific use cases within the application.

#### Adding a custom context

```php
use DrevOps\EnvironmentDetector\Contexts\ContextInterface;

class CustomContext implements ContextInterface {
  public function active(): bool {
    return class_exists('MyFramework');
  }

  public function contextualize(): void {
    // Apply generic context changes
    global $configuration;
    $configuration['custom_value'] = $_SERVER['custom_value'] ?? 'default';
  }

  public function id(): string {
    return 'myframework';
  }

  public function label(): string {
    return 'My Framework';
  }
}

// Register the custom context during initialization
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
