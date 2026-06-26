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

## Optimization notes

What the suite surfaced, and where it landed:

1. **Container probing ran per stack.** Every container-family stack (Ddev, Lando, Container) inherits `Container::isContainer()`, so a single native-host detection re-ran the same env/filesystem probe up to three times. `Container::isContainer()` now memoises its result for the run (cleared on `Environment::reset()`), so the probe runs once while a subclass overriding `isContainer()` still opts out. This cut `benchDetectDrupalNative` by ~19% locally (more on Linux, where the probe actually reads `/proc/1/cgroup`). The remaining single probe is inherent to detecting containerisation.
2. **Duplicated dispatch.** `AbstractPlatform::contextualize()` and `AbstractStack::contextualize()` were byte-identical; they now share the `DispatchesContextualization` trait, which dispatches the built-in Drupal context through its typed interface and falls back to reflection only for custom contexts.
3. **`is()` reads the env var, by design.** `Environment::is()` calls `getenv('ENVIRONMENT_TYPE')` each time. This is deliberate: the env var is the single source of truth for the type - both the override input and the published output. Caching it in a static would create a second store that can silently diverge, so the per-call `getenv()` stays.

## Comparison and baseline

The benchmark runs on every pull request and every push to `main`, posting its comparison against the committed baseline as a PR comment and as the running trend on the "Performance benchmarks" issue. It is a **tracking signal, not a pass/fail gate** - it never fails CI.

A hard gate is deliberately not used: each CI run lands on a different shared GitHub runner, and runner speed varies ~15-25% between jobs, so a fresh run compared against a committed baseline shifts that much run-to-run regardless of the code. Generating the baseline on CI does not fix this - the candidate run is still a different runner. The only way to a reliable hard gate is to measure the baseline and the candidate in the same job on the same runner so the runner offset cancels; that is a possible future enhancement.

The committed baseline in `.phpbench/storage/` is the comparison reference, generated on a CI runner. Refresh it by running the "Benchmark PHP" workflow manually (Run workflow / `workflow_dispatch`) on the target branch: the job regenerates the baseline on the runner, removes the previous one, and commits the single replacement back. Because a pull request runs the workflow file from its own branch, dispatching it on a feature branch refreshes that branch's baseline directly - no merge to the default branch is needed first. The fastest sub-microsecond subjects carry the most variance; treat their deltas as noise.
