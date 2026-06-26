# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

**Always use the Composer scripts below - never invoke the underlying binaries directly** (`vendor/bin/phpstan`, `vendor/bin/phpcs`, `vendor/bin/phpunit`, `vendor/bin/rector`, `vendor/bin/phpbench`, etc.). The scripts set the correct config, paths, and environment, so a raw invocation can silently diverge from CI.

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
- Inner ring - the substrate the environment runs in (the native host, a container, or a more specific container like DDEV or Lando)
- A specific container extends `Container`; `Container` is the generic container fallback, and `Native` (the bare-metal host) is registered last so a stack is always active
- Stacks never carry the environment type (no `type()`) and never collide with a platform
- Exactly one stack is always active - the most specific container that matches, or the native host on bare metal; the active stack may contribute settings

**Context System (`src/Contexts/`)**
- The app/framework where settings land - apply changes after environment detection
- Currently supports Drupal context modifications
- Contexts implement `ContextInterface` and extend `AbstractContext`
- At most one context can be active at a time

### Key Patterns

- **Static Facade**: All functionality accessed through `Environment::` static methods
- **Nested Rings**: Platform (decides type) wraps Stack (substrate, no type) wraps Context (settings target)
- **Constant-based Loading**: Platforms, stacks and contexts loaded from protected constants (no filesystem scanning)
- **Associative Storage**: Platforms/stacks/contexts stored by ID as keys for O(1) duplicate detection
- **Environment Variable Priority**: If `ENVIRONMENT_TYPE` is pre-set, it overrides detection
- **Fallback Safety**: Defaults to `development` type to prevent production settings in dev when a platform is active but its tier is unknown

### Usage Flow

1. `Environment::init()` - Initialize detection and populate env var
2. Platform discovery finds the single active platform based on environment variables/files
3. The active platform returns the environment type (no platform: `ci` when a `CI` signal is present, otherwise `local`)
4. Optional context applies generic changes; the active platform and the active stack then apply their own context-specific changes
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

- PHPBench measures the real detection and contextualization hot paths. Cold-path subjects reset the detector each revolution (so a full once-per-request detection is measured, not a warm early return); the warm subject keeps a per-iteration reset to measure repeated checks.
- Benchmarks in `benchmarks/` directory measure:
  - `DetectionBenchmark` - cold detection of the local environment, of an active Drupal context on the native and container stacks, of an active platform resolving its type, and of the full platform + stack + context path
  - `InitBenchmark` - repeated `isProd()` type checks once the environment is warm
  - `DiscoveryBenchmark` - how registering extra platforms/stacks/contexts (0,1,2,5,10) scales the cold detection cost
- Reports generated as JSON and HTML in `.logs/performance-report.*`
- CI runs performance tests without xdebug/pcov for accurate measurements
- See `benchmarks/README.md` for the latest performance verdict and the identified optimization opportunities

#### Baseline Management

- Baselines stored in `.phpbench/storage/` directory (tracked in git)
- The committed baseline is generated on the CI runner, so the gate compares like-for-like rather than across machines
- CI automatically compares each pull request against the baseline with a ±15% threshold, set above the run-to-run noise floor of these microsecond-scale subjects (a tighter gate produced false failures, including on docs-only changes)
- Refresh the baseline by running the "Benchmark PHP" workflow manually (`workflow_dispatch`) on the target branch; the job regenerates it on the runner, removes the previous one, and commits the single replacement back (no merge to the default branch needed first)
- Performance regressions exceeding the ±15% threshold will fail CI builds

## File Structure

```
src/
├── Environment.php          # Main facade class
├── Platforms/               # Outermost ring - hosting and CI; decides the type
│   ├── PlatformInterface.php
│   ├── AbstractPlatform.php
│   └── [SpecificPlatform].php
├── Stacks/                  # Inner ring - substrate (native/container/ddev/lando); no type
│   ├── StackInterface.php
│   ├── AbstractStack.php
│   └── [SpecificStack].php
└── Contexts/                # App/framework where settings land
    ├── ContextInterface.php
    ├── AbstractContext.php
    └── [SpecificContext].php
```
