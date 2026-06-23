# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

### Linting and Code Quality
- `composer lint` - Run all code quality checks (PHPCS, PHPStan, Rector dry-run)
- `composer lint-fix` - Fix code quality issues (Rector + PHPCBF)

### Testing
- `composer test` - Run PHPUnit tests without coverage
- `composer test-coverage` - Run PHPUnit tests with coverage reports

### Performance Benchmarking
- `composer benchmark` - Run PHPBench performance tests and compare against baseline
- `composer benchmark-baseline` - Update the performance baseline (after performance improvements or significant changes)
- `composer benchmark -- --filter=DiscoveryBenchmark` - Run specific performance benchmark

### Single Test Execution
- `composer test -- --filter=testMethodName` - Run specific test method

### Other Commands
- `composer reset` - Clean vendor directory and composer.lock

## Architecture Overview

This is a PHP library for zero-config environment type detection across multiple hosting providers and development environments.

Detection is modelled as nested rings. A run wraps from an outer ring down to the application: a **Platform** contains a **Stack**, which contains the runtime, which contains the application **Context**. Only the outermost ring (Platform) decides the environment type; an empty outer ring means the run is local.

### Core Components

**Environment Class (`src/Environment.php`)**
- Main entry point and static facade
- Manages platform/stack/context registration and discovery
- Provides convenience methods: `isLocal()`, `isProd()`, `isDev()`, etc.
- Handles the fallback type
- Populates `ENVIRONMENT_TYPE` environment variable

**Platform System (`src/Platforms/`)**
- The outermost ring - hosting providers (Acquia, Lagoon, etc.) and CI services (GitHub Actions, etc.)
- Platforms implement `PlatformInterface` and extend `AbstractPlatform`
- The ONLY ring that carries the environment type
- At most one platform can be active at a time (two active platforms is a misconfiguration and throws)
- Platforms map internal states to standard types: `local`, `ci`, `development`, `preview`, `stage`, `production`

**Stack System (`src/Stacks/`)**
- Inner rings - the substrate the environment runs in (Docker, DDEV, Lando)
- Stacks implement `StackInterface` and extend `AbstractStack`
- Stacks never carry the environment type (no `type()`) and never collide with a platform
- Multiple stacks can be active at the same time; each may contribute settings

**Context System (`src/Contexts/`)**
- The app/framework where settings land - apply changes after environment detection
- Currently supports Drupal context modifications
- Contexts implement `ContextInterface` and extend `AbstractContext`
- At most one context can be active at a time

### Key Patterns

- **Static Facade**: All functionality accessed through `Environment::` static methods
- **Nested Rings**: Platform (decides type) wraps Stack (substrate, inherits type) wraps Context (settings target)
- **Constant-based Loading**: Platforms, stacks and contexts loaded from protected constants (no filesystem scanning)
- **Associative Storage**: Platforms/stacks/contexts stored by ID as keys for O(1) duplicate detection
- **Environment Variable Priority**: If `ENVIRONMENT_TYPE` is pre-set, it overrides detection
- **Fallback Safety**: Defaults to `development` type to prevent production settings in dev when a platform is active but its tier is unknown

### Usage Flow

1. `Environment::init()` - Initialize detection and populate env var
2. Platform discovery finds the single active platform based on environment variables/files
3. The active platform returns the environment type (no platform: `ci` when a `CI` signal is present, otherwise `local`)
4. Optional context applies generic changes; the active platform and every active stack then apply their own context-specific changes
5. Result stored in `ENVIRONMENT_TYPE` env var

## Code Standards

- PHP 8.3+ required with strict types
- Follows Drupal coding standards via PHPCS
- PHPStan level 9 static analysis
- Rector for code modernization
- All code must have `declare(strict_types=1)`

## Testing Approach

- PHPUnit 12 with strict configuration
- Test base classes: `EnvironmentDetectorTestCase`, `PlatformTestCase`, `StackTestCase`, `ContextTestCase`
- Coverage reports generated in `.logs/.coverage-html/`
- Fixtures stored in `tests/fixtures/` for platform-specific data

### Performance Testing

- PHPBench for measuring constant-based loading performance
- Benchmarks in `benchmarks/` directory measure:
  - Platform and stack loading via constants (no filesystem scanning)
  - Context loading performance
  - Full initialization overhead
  - Type checking after caching
  - Multiple platform/stack/context registration impact with scaling analysis (1,2,5,10 additions)
- Reports generated as JSON and HTML in `.logs/performance-report.*`
- CI runs performance tests without xdebug/pcov for accurate measurements

#### Baseline Management

- Baselines stored in `.phpbench/storage/` directory (tracked in git)
- CI automatically compares performance against baseline with ±5% threshold
- Baselines are updated manually by running `composer benchmark-baseline` and committing the changes
- Performance regressions exceeding 5% threshold will fail CI builds

## File Structure

```
src/
├── Environment.php          # Main facade class
├── Platforms/               # Outermost ring - hosting and CI; decides the type
│   ├── PlatformInterface.php
│   ├── AbstractPlatform.php
│   └── [SpecificPlatform].php
├── Stacks/                  # Inner ring - substrate (docker/ddev/lando); no type
│   ├── StackInterface.php
│   ├── AbstractStack.php
│   └── [SpecificStack].php
└── Contexts/                # App/framework where settings land
    ├── ContextInterface.php
    ├── AbstractContext.php
    └── [SpecificContext].php
```
