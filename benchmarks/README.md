# Performance benchmarks

PHPBench suite measuring the environment detector's real hot paths. Run `composer benchmark` to compare against the committed baseline locally; the committed baseline itself is regenerated on CI (see Gate and baseline).

## What is measured

- **`DetectionBenchmark`** - the once-per-request cold path. Each subject resets the detector every revolution, so a full discovery, type resolution and settings application is measured rather than the early return a warm `init()` takes. Subjects: local (no platform, no context), an active Drupal context on the native and container stacks, an active platform resolving its type, and the full platform + stack + context path.
- **`InitBenchmark`** - repeated `isProd()` checks once the environment is warm (the detector is reset once per iteration, so the measured cost is the repeated checks after detection has settled).
- **`DiscoveryBenchmark`** - how registering extra platforms/stacks/contexts (0, 1, 2, 5, 10) scales the cold detection cost.

## Verdict

Performance is acceptable. Detection is microsecond-scale with no algorithmic hotspot: loading is constant-based (no filesystem scanning) and the active platform, stack and context are each resolved once and cached. Most full cold detections are single-digit to low-double-digit microseconds and a warm type check is a fraction of a microsecond; the native-host Drupal path is the one notable outlier (see Optimization opportunities).

Reference points from the CI runner (Linux, PHP 8.3) - the same environment the baseline is generated on. Absolute times are environment-specific; use them to spot large shifts:

| Path | Cold time |
| --- | --- |
| `benchDetectLocal` (local, no context) | ~8 μs |
| `benchDetectPlatform` (active platform + type) | ~8 μs |
| `benchDetectFullStack` (platform + container + Drupal) | ~12 μs |
| `benchDetectDrupalContainer` (Drupal context, container) | ~12 μs |
| `benchDetectDrupalNative` (Drupal context, native host) | ~41 μs |
| `benchIsAfterInit` warm `isProd()` | sub-microsecond per call |

Peak memory is ~2 MB across all subjects.

## Optimization opportunities

Candidates for a dedicated performance pass, with the evidence the suite surfaces:

1. **Native-host detection cost.** `benchDetectDrupalNative` (~41 μs) is dramatically slower than `benchDetectDrupalContainer` (~12 μs) despite the container doing more contextualization work. The native path pays for `Container::isContainer()`'s filesystem probes (`file_exists('/.dockerenv')`, `file_exists('/.dockerinit')`, `is_readable('/proc/1/cgroup')`) and, during contextualization, the reflection fallback in `AbstractStack::contextualize()` / `AbstractPlatform::contextualize()` (`Native` is not a `DrupalContextualizerInterface`, so a method name is built via `ReflectionClass::getShortName()`). The container path short-circuits `isContainer()` on an env var and dispatches through the typed fast path. This gap is the suite's clearest optimization target.
2. **`is()` re-reads the env var.** `Environment::is()` calls `getenv('ENVIRONMENT_TYPE')` on every invocation even though the type is already resolved. Caching it in a static property realizes the documented "statically cached" design and speeds the repeated-check path.
3. **Duplicated dispatch.** `AbstractPlatform::contextualize()` and `AbstractStack::contextualize()` are identical; the shared dispatch can move to a trait.

## Gate and baseline

The committed baseline in `.phpbench/storage/` is generated on the CI runner, not locally, so the gate compares like-for-like rather than across machines. Refresh it by running the "Benchmark PHP" workflow manually (Run workflow / `workflow_dispatch`) on the target branch: the job regenerates the baseline on the runner, removes the previous one, and commits the single replacement back. Because a pull request runs the workflow file from its own branch, dispatching it on a feature branch refreshes that branch's baseline directly - no merge to the default branch is needed first.

CI compares each pull-request run against that baseline and fails on a regression beyond **±15%**. The threshold sits above the run-to-run noise floor of these microsecond subjects: even on one machine with no code change, the fastest subjects drift ~10%, so a tighter gate produces false failures (including on docs-only changes). The fastest sub-microsecond warm subjects carry the most variance; treat their deltas as noise.
