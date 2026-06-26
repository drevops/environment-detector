# Performance benchmarks

PHPBench suite measuring the environment detector's real hot paths. Run `composer benchmark` to compare against the committed baseline, or `composer benchmark-baseline` to regenerate it.

## What is measured

- **`DetectionBenchmark`** - the once-per-request cold path. Each subject resets the detector every revolution, so a full discovery, type resolution and settings application is measured rather than the early return a warm `init()` takes. Subjects: local (no platform, no context), an active Drupal context on the native and container stacks, an active platform resolving its type, and the full platform + stack + context path.
- **`InitBenchmark`** - repeated `isProd()` checks once the environment is warm (the detector is reset once per iteration, so the measured cost is the repeated checks after detection has settled).
- **`DiscoveryBenchmark`** - how registering extra platforms/stacks/contexts (0, 1, 2, 5, 10) scales the cold detection cost.

## Verdict

Performance is acceptable. Detection is microsecond-scale with no algorithmic hotspot: loading is constant-based (no filesystem scanning) and the active platform, stack and context are each resolved once and cached. A full cold detection is single-digit to low-double-digit microseconds; a warm type check is a fraction of a microsecond.

Reference points, measured on an Apple Silicon host (arm64, PHP 8.4) on 2026-06-26. Absolute times are machine-specific - use them to spot large shifts, not to compare across machines:

| Path | Cold time |
| --- | --- |
| `benchDetectLocal` (local, no context) | ~9 μs |
| `benchDetectPlatform` (active platform + type) | ~9 μs |
| `benchDetectFullStack` (platform + container + Drupal) | ~14 μs |
| `benchDetectDrupalContainer` (Drupal context, container) | ~15 μs |
| `benchDetectDrupalNative` (Drupal context, native host) | ~18 μs |
| `benchIsAfterInit` warm `isProd()` | ~0.25 μs per call |

Peak memory is ~2 MB across all subjects.

## Optimization opportunities

Candidates for a dedicated performance pass, with the evidence the suite surfaces:

1. **Native-host detection cost.** `benchDetectDrupalNative` (~18 μs) is slower than `benchDetectDrupalContainer` (~15 μs) despite the container doing more contextualization work. The native path pays for `Container::isContainer()`'s filesystem probes (`file_exists('/.dockerenv')`, `file_exists('/.dockerinit')`, `is_readable('/proc/1/cgroup')`) and, during contextualization, the reflection fallback in `AbstractStack::contextualize()` / `AbstractPlatform::contextualize()` (`Native` is not a `DrupalContextualizerInterface`, so a method name is built via `ReflectionClass::getShortName()`). The container path short-circuits `isContainer()` on an env var and dispatches through the typed fast path.
2. **`is()` re-reads the env var.** `Environment::is()` calls `getenv('ENVIRONMENT_TYPE')` on every invocation even though the type is already resolved. Caching it in a static property realizes the documented "statically cached" design and speeds the repeated-check path.
3. **Duplicated dispatch.** `AbstractPlatform::contextualize()` and `AbstractStack::contextualize()` are identical; the shared dispatch can move to a trait.

## Gate and baseline

CI compares each run against the committed baseline in `.phpbench/storage/` and fails on a regression beyond **±15%**. The threshold sits above the run-to-run and cross-machine noise floor of these microsecond subjects: even on one machine with no code change, the fastest subjects drift ~10%, so a tighter gate produces false failures (including on docs-only changes).

The baseline is generated locally and committed, while CI runs on a different machine, so absolute times differ and the ±15% tolerance absorbs the offset. Generating the baseline on the CI runner would remove the cross-machine component and allow a tighter gate - a candidate future improvement. The fastest sub-microsecond warm subjects carry the most variance; treat their deltas as noise.
