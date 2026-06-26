# Performance benchmarks

PHPBench suite measuring the environment detector's real hot paths. Run `composer benchmark` to compare against the committed baseline locally; the committed baseline itself is regenerated on CI (see Gate and baseline).

## What is measured

- **`DetectionBenchmark`** - the once-per-request cold path. Each subject resets the detector every revolution, so a full discovery, type resolution and settings application is measured rather than the early return a warm `init()` takes. Subjects: local (no platform, no context), an active Drupal context on the native and container stacks, an active platform resolving its type, and the full platform + stack + context path.
- **`InitBenchmark`** - repeated `isProd()` checks once the environment is warm (the detector is reset once per iteration, so the measured cost is the repeated checks after detection has settled).
- **`DiscoveryBenchmark`** - how registering extra platforms/stacks/contexts (0, 1, 2, 5, 10) scales the cold detection cost.

## Verdict

Performance is acceptable. Detection is microsecond-scale with no algorithmic hotspot: loading is constant-based (no filesystem scanning) and the active platform, stack and context are each resolved once and cached. Most full cold detections are single-digit to low-double-digit microseconds and a warm type check is a fraction of a microsecond; the native-host Drupal path is the one notable outlier (see Optimization notes).

Reference points from the CI runner (Linux, PHP 8.3) - the same environment the baseline is generated on. Absolute times are environment-specific; use them to spot large shifts:

| Path | Cold time |
| --- | --- |
| `benchDetectLocal` (local, no context) | ~8 μs |
| `benchDetectPlatform` (active platform + type) | ~8 μs |
| `benchDetectFullStack` (platform + container + Drupal) | ~12 μs |
| `benchDetectDrupalContainer` (Drupal context, container) | ~12 μs |
| `benchDetectDrupalNative` (Drupal context, native host) | ~28 μs |
| `benchIsAfterInit` warm `isProd()` | sub-microsecond per call |

Peak memory is ~2 MB across all subjects.

## Cost in context

Every figure here is microseconds (μs), and detection runs once per request during settings load. A microsecond is a thousandth of a millisecond, so the scale is easy to misread: a Drupal page aiming for a ~200 ms response has a budget of 200,000 μs, and a full cold detection of a few tens of microseconds is on the order of 0.01% of it. Optimising detection further moves a number a real request never feels.

For scale, within a single request:

| Work | Order of magnitude |
| --- | --- |
| Full page response (target) | ~200 ms (200,000 μs) |
| Framework class autoloading, cold | ~10-16 ms |
| One database query | ~0.2-2 ms (200-2,000 μs) |
| **Full cold environment detection** | **~0.01-0.03 ms (tens of μs)** |
| One `getenv()` | ~0.001 ms (~1 μs) |

What keeps this true in production is opcode caching. PHP compiles each source file to bytecode on first use; OPcache stores that bytecode in shared memory and serves it on every later request, so the per-request cost of loading the detector's classes (the autoloader walking files, parsing them, and compiling them) is paid once at deploy time rather than on every request. Preloading goes further and resolves classes from shared memory before the first request runs. The effect is that the object-oriented structure (the platform, stack and context classes, their interfaces and the trait) is close to free once warm: what remains at runtime is the irreducible work of reading a handful of environment variables and, for an unlabelled container, a short fallback probe (environment markers, then `/.dockerenv`, then `/proc/1/cgroup`).

This is why the detector stays a set of small classes rather than one flattened procedural file. Collapsing it would remove only the structural overhead OPcache has already amortised, a few microseconds in production, while keeping every probe the flat version would still have to run, and it would trade away the testability and extensibility the ring model provides. The benchmark exists to catch a regression that makes detection cost orders of magnitude more, not to shave microseconds a page never notices.

## Optimization notes

What the suite surfaced, and where it landed:

1. **Container probing ran per stack.** The detection loop tested every container-family stack, so a native-host detection re-ran the `isContainer()` env/filesystem probe up to three times. Two changes remove that: the specific stacks (DDEV, Lando) are matched by the definitive marker their tool sets and never probe, and `Container::isContainer()` memoises its result for the run (cleared on `Environment::reset()`) so the one remaining generic-container probe is computed once. `benchDetectDrupalNative` sits ~19% below the old per-stack probing locally, and about half that on Linux where the probe reads `/proc/1/cgroup`. The single generic probe that remains is inherent to detecting an unlabelled container.
2. **Duplicated dispatch.** `AbstractPlatform::contextualize()` and `AbstractStack::contextualize()` were byte-identical; they now share the `DispatchesContextualization` trait, which dispatches the built-in Drupal context through its typed interface and falls back to reflection only for custom contexts.
3. **`is()` reads the env var, by design.** `Environment::is()` calls `getenv('ENVIRONMENT_TYPE')` each time. This is deliberate: the env var is the single source of truth for the type - both the override input and the published output. Caching it in a static would create a second store that can silently diverge, so the per-call `getenv()` stays.

## Comparison and baseline

The benchmark runs on every pull request and every push to `main`, posting its comparison against the committed baseline as a PR comment and as the running trend on the "Performance benchmarks" issue. It is a **tracking signal, not a pass/fail gate** - it never fails CI.

A hard gate is deliberately not used: each CI run lands on a different shared GitHub runner, and runner speed varies ~15-25% between jobs, so a fresh run compared against a committed baseline shifts that much run-to-run regardless of the code. Generating the baseline on CI does not fix this - the candidate run is still a different runner. The only way to a reliable hard gate is to measure the baseline and the candidate in the same job on the same runner so the runner offset cancels; that is a possible future enhancement.

The committed baseline in `.phpbench/storage/` is the comparison reference, generated on a CI runner. Refresh it by running the "Benchmark PHP" workflow manually (Run workflow / `workflow_dispatch`) on the target branch: the job regenerates the baseline on the runner, removes the previous one, and commits the single replacement back. Because a pull request runs the workflow file from its own branch, dispatching it on a feature branch refreshes that branch's baseline directly - no merge to the default branch is needed first. The fastest sub-microsecond subjects carry the most variance; treat their deltas as noise.
