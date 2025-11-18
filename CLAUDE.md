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
- **Constant-based Loading**: Providers and contexts loaded from protected constants (no filesystem scanning)
- **Early Termination**: Optimized conflict detection stops at first duplicate found
- **Associative Storage**: Providers/contexts stored by ID as keys for O(1) duplicate detection
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
- Test base classes: `EnvironmentDetectorTestCase`, `ProviderTestCase`, `ContextTestCase`
- Coverage reports generated in `.logs/.coverage-html/`
- Fixtures stored in `tests/fixtures/` for provider-specific data

### Performance Testing

- PHPBench for measuring constant-based loading and early termination performance
- Benchmarks in `benchmarks/` directory measure:
  - Provider loading via constants (no filesystem scanning)
  - Context loading performance
  - Full initialization overhead
  - Type checking after caching
  - Multiple provider registration impact with scaling analysis (1,2,5,10 additions)
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
├── Providers/              # Environment detection providers
│   ├── ProviderInterface.php
│   ├── AbstractProvider.php
│   └── [SpecificProvider].php
└── Contexts/              # Framework integration contexts
    ├── ContextInterface.php
    ├── AbstractContext.php
    └── [SpecificContext].php
```
