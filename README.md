<p align="center">
  <a href="" rel="noopener">
  <img width=100px height=100px src="logo.png" alt="Environment Detector"></a>
</p>

<h1 align="center">Auto-detect environment type</h1>

<div align="center">

[![GitHub Issues](https://img.shields.io/github/issues/drevops/environment-detector.svg)](https://github.com/drevops/environment-detector/issues)
[![GitHub Pull Requests](https://img.shields.io/github/issues-pr/drevops/environment-detector.svg)](https://github.com/drevops/environment-detector/pulls)
[![Test PHP](https://github.com/drevops/environment-detector/actions/workflows/test-php.yml/badge.svg)](https://github.com/drevops/environment-detector/actions/workflows/test-php.yml)
[![codecov](https://codecov.io/gh/drevops/environment-detector/graph/badge.svg?token=7WEB1IXBYT)](https://codecov.io/gh/drevops/environment-detector)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/drevops/environment-detector)
![LICENSE](https://img.shields.io/github/license/drevops/environment-detector)
![Renovate](https://img.shields.io/badge/renovate-enabled-green?logo=renovatebot)

</div>

---

## Features

- Detects environment type: `local`, `ci`, `dev`, `preview`, `stage`, `prod`, or custom
- Supports popular providers
- Detects custom contexts: [Drupal](src/Contexts/Drupal.php)
- Simple API to access environment and provider data
- Allows adding a custom provider
- Optional override for consumer-level customization of the existing matching
  logic

## Supported providers

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

## Installation

```bash
composer require drevops/environment-detector
```

## Approach

The type detection is based on a three-part process:

1. Provider Detection: Hosting environments expose certain metadata (e.g.,
   environment variables or files). These are used to identify the active
   hosting provider.
2. Type Mapping: Each provider can contextually map its environment information
   to a predefined or custom type (e.g., dev, stage, prod). This type becomes
   the central reference point for targeting environment-specific behavior,
   configuration, or deployment logic from within a consumer application.
3. Optional context handling: A provider can identify a context where it runs
   (framework, CMS, runtime etc.) and make required adjustments.

## Usage

Detects the environment type based on the registered providers. This package
provides a set of built-in providers, but custom provider can be added as
well.

### Providers

The environment type is determined by the "active" provider - a registered
provider that has detected the current environment using its own logic.
Only one provider can be active at a time (otherwise an exception is thrown).

If no provider is active an exception is thrown. This is to ensure that the
environment type is always detected and that the application does not run
with an unknown environment type. Add a custom provider using `addProvider()`
to register a new provider implementing `ProviderInterface`.

The environment type returned by a provider can be overridden by a callback
set using the `setOverride()` method. The callback will receive the
currently active provider, and the currently discovered environment type as
arguments. This allows to add custom types and override the detected type
based on the custom logic. The advantage of such an approach is that the
active provider is still discovered using the provider's own logic, and the
override callback is used only to change the environment type.

If an active provider is not able to determine the environment type, it
returns `NULL`. In this case, the fallback environment type is used.
The fallback environment type can be overridden using the `setFallback()`
method.

The default fallback environment type is `Environment::DEV` - this is to make
sure that, in case of misconfiguration, the application does not apply local
settings in production or production settings in local - 'dev' type is
the safest default.

The discovered type is statically cached to be performant. The cache can be
reset using the `reset()` method.

### Contexts

Contexts are used to apply environment-specific changes to the application.

The active context is determined by the "active" context - a registered
context that has detected the current context using its own logic.

A context may provide generic changes that are applied to the application.
A provider may provide provider-specific context changes that are applied to
the application as well.

For example, a Drupal context applies the changes to the global `$settings`
array, while a Lagoon provider's `contextualize()` method adds more
Lagoon-specific changes to the `$settings` array. In this case, Acquia Cloud
provider provides their own Acquia Cloud-specific changes to the `$settings`
array.

The goal of this package is to have enough context changes to cover the most
common use cases, but also to allow adding custom contexts to cover the
specific use cases within the application.

### `ENVIRONMENT_TYPE`

The detected environment type is stored in the `ENVIRONMENT_TYPE` environment
variable. This variable can be used in the application to apply environment-
specific changes.

If the `ENVIRONMENT_TYPE` environment variable is already set, the value
will be used as the environment type. This is useful in cases when the
environment type needs to be set manually to override the detected type (for
example, when debugging the application).

### Init

```php
use DrevOps\EnvironmentDetector\Environment;

Environment::init();                    // Init and populate the `ENVIRONMENT_TYPE` env var.
$env_type = getenv('ENVIRONMENT_TYPE'); // Use the `ENVIRONMENT_TYPE` env var as needed.
...
if ($env_type === Environment::LOCAL) {
 // Apply local settings.
}
```

### Pre-defined types

```php
use DrevOps\EnvironmentDetector\Environment;

Environment::init();

if (Environment::isProd()) {
  // Production logic.
}

if (Environment::isStage()) {
  // Stage logic.
}

if (Environment::isDev()) {
  // Dev logic.
}

if (Environment::isCi()) {
  // CI logic.
}

if (Environment::isLocal()) {
  // Local logic.
}

// Custom type 'qa' (see below).
if (Environment::is('qa')) {
  // Local logic.
}

```

### Provider data

Sometimes you may need to access provider-specific data. This can be done using
the `provider()->data()` method.

```php

if(Environment::provider()->id() == 'acquia') {
  // Acquia-specific logic.
}

if(Environment::provider()->data()['HOSTING_BRANCH'] == 'main') {
  // Main branch logic.
}

```

### Override type resolution

You can override the resolved type based on the active provider and its returned
type. You may return an existing or custom type.

The advantage of this approach is that the provider resolution logic, and it's
type are encapsulated within the provider itself, while the resulting type
can be contextually overridden based on the discovered values.

```php
use DrevOps\EnvironmentDetector\Environment;
use Env\Provider;

Environment::setOverride(function (Provider $provider, ?string $current): ?string {
  if ($current === Environment::DEVELOPMENT && $provider->id() === 'tugboat') {
    return 'qa';
  }
  return $current;
});
```

### Setting a fallback type

If an environment type is not detected, a fallback `Environment::DEV` will be
returned by default.

You can set a different default type using the `setFallback()` method.

```php

Environment::setFallback(Environment::DEV);

```

### Adding a custom provider

```php
use DrevOps\EnvironmentDetector\Provider;
use DrevOps\EnvironmentDetector\Environment;

class CustomHosting implements Provider {

  public const string ID = 'customhosting';

  public const string LABEL = 'Custom Hosting';

  protected function envPrefixes(): array {
    return ['CH_'];
  }

  public function active(): bool {
    return isset($_SERVER['CUSTOM_ENV']);
  }

  public function data(): array {
    return ['CUSTOM_ENV' => $_SERVER['CUSTOM_ENV'] ?? NULL];
  }

  public function type(): ?string {
    return match ($_SERVER['CUSTOM_ENV_TYPE'] ?? NULL) {
      'dev' => Environment::DEVELOPMENT,
      'qa' => 'qa', // Custom type.
      'live' => Environment::PRODUCTION,
      default => NULL,
    };
  }
}

// Register the provider.
Environment::addProvider(new CustomHosting());
```

### Adding a custom context

```php
use DrevOps\EnvironmentDetector\Context;

class CustomContext implements Context {

  public const string ID = 'customcontext';

  public const string LABEL = 'Custom Context';

  public function active(): bool {
    return isset($_SERVER['CUSTOM_CONTEXT']);
  }

  public function contextualize(): void {
    global $settings;
    $settings['custom_value'] = $_SERVER['custom_value'];
  }
}
```

## Maintenance

    composer install
    composer lint
    composer test

---
_This repository was created using
the [Scaffold](https://getscaffold.dev/) project
template_
