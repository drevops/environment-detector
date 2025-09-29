# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

### Linting and Code Quality
- `composer lint` - Run all code quality checks (PHPCS, PHPStan, Rector dry-run)
- `composer lint-fix` - Fix code quality issues (Rector + PHPCBF)

### Testing
- `composer test` - Run PHPUnit tests without coverage
- `composer test-coverage` - Run PHPUnit tests with coverage reports
- `composer test-performance` - Run PHPBench performance tests

### Single Test Execution
- `./vendor/bin/phpunit tests/EnvironmentTest.php` - Run specific test file
- `./vendor/bin/phpunit --filter testMethodName` - Run specific test method
- `./vendor/bin/phpbench run benchmarks/DiscoveryBench.php` - Run specific performance benchmark

### Other Commands
- `composer reset` - Clean vendor directory and composer.lock

## Architecture Overview

This is a PHP library for zero-config environment type detection across multiple hosting providers and development environments.

### Core Components

**Environment Class (`src/Environment.php`)**
- Main entry point and static facade
- Manages provider/context registration and discovery
- Provides convenience methods: `isLocal()`, `isProd()`, `isDev()`, etc.
- Handles override callbacks and fallback types
- Populates `ENVIRONMENT_TYPE` environment variable

**Provider System (`src/Providers/`)**
- Each provider detects specific hosting environments (Acquia, Lagoon, etc.)
- Providers implement `ProviderInterface` and extend `AbstractProvider`
- Only one provider can be active at a time
- Providers map internal states to standard types: `local`, `ci`, `development`, `preview`, `stage`, `production`

**Context System (`src/Contexts/`)**
- Apply framework-specific changes after environment detection
- Currently supports Drupal context modifications
- Contexts implement `ContextInterface`

### Key Patterns

- **Static Facade**: All functionality accessed through `Environment::` static methods
- **Auto-discovery**: Providers and contexts are automatically registered by scanning directories
- **Environment Variable Priority**: If `ENVIRONMENT_TYPE` is pre-set, it overrides provider detection
- **Fallback Safety**: Defaults to `development` type to prevent production settings in dev

### Usage Flow

1. `Environment::init()` - Initialize detection and populate env var
2. Provider discovery finds active provider based on environment variables/files
3. Provider returns environment type
4. Optional context applies framework-specific changes
5. Result stored in `ENVIRONMENT_TYPE` env var

## Code Standards

- PHP 8.3+ required with strict types
- Follows Drupal coding standards via PHPCS
- PHPStan level 9 static analysis
- Rector for code modernization
- All code must have `declare(strict_types=1)`

## Testing Approach

- PHPUnit 12 with strict configuration
- Test base classes: `TestBase`, `ProviderTestBase`, `ContextTestBase`
- Coverage reports generated in `.logs/.coverage-html/`
- Fixtures stored in `tests/fixtures/` for provider-specific data

### Performance Testing

- PHPBench for measuring filesystem scanning performance
- Benchmarks in `benchmarks/` directory measure:
  - Provider discovery via `scandir()` operations
  - Context discovery performance
  - Full initialization overhead
  - Type checking after caching
  - Multiple provider registration impact
- Reports generated as JSON and HTML in `.logs/performance-report.*`
- CI runs performance tests without xdebug/pcov for accurate measurements

## File Structure

```
src/
├── Environment.php          # Main facade class
├── Providers/              # Environment detection providers
│   ├── ProviderInterface.php
│   ├── AbstractProvider.php
│   └── [SpecificProvider].php
└── Contexts/              # Framework integration contexts
    ├── ContextInterface.php
    ├── AbstractContext.php
    └── [SpecificContext].php
```
