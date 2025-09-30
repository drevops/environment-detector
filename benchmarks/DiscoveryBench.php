<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Benchmarks;

use DrevOps\EnvironmentDetector\Providers\ProviderInterface;
use DrevOps\EnvironmentDetector\Contexts\ContextInterface;
use DrevOps\EnvironmentDetector\Environment;
use PhpBench\Attributes as Bench;

/**
 * Benchmark for provider and context loading performance.
 *
 * This benchmark measures the performance of loading providers and contexts
 * from constants and instantiating classes, including the impact of adding
 * custom providers and contexts.
 */
class DiscoveryBench {

  /**
   * Set up the benchmark environment.
   */
  public function setUp(): void {
    // Reset environment to ensure clean state for each iteration.
    Environment::reset();
  }

  /**
   * Benchmark provider loading from constants.
   *
   * This measures the time it takes to load all built-in providers
   * from constants and instantiate classes.
   */
  #[Bench\Revs(100)]
  #[Bench\Iterations(25)]
  #[Bench\Warmup(2)]
  #[Bench\RetryThreshold(5)]
  #[Bench\BeforeMethods(['setUp'])]
  public function benchProviderLoading(): void {
    // This will trigger constant-based loading and provider instantiation.
    Environment::providers();
  }

  /**
   * Benchmark context loading from constants.
   *
   * This measures the time it takes to load all built-in contexts
   * from constants and instantiate classes.
   */
  #[Bench\Revs(100)]
  #[Bench\Iterations(25)]
  #[Bench\Warmup(2)]
  #[Bench\RetryThreshold(5)]
  #[Bench\BeforeMethods(['setUp'])]
  public function benchContextLoading(): void {
    // This will trigger constant-based loading and context instantiation.
    Environment::contexts();
  }

  /**
   * Benchmark full environment initialization.
   *
   * This measures the complete initialization process including
   * provider discovery, context discovery, and environment type resolution.
   */
  #[Bench\Revs(50)]
  #[Bench\Iterations(25)]
  #[Bench\Warmup(2)]
  #[Bench\RetryThreshold(5)]
  #[Bench\BeforeMethods(['setUp'])]
  public function benchFullInitialization(): void {
    // This will trigger complete environment initialization.
    Environment::init();
  }

  /**
   * Benchmark environment type checking after initialization.
   *
   * This measures the performance of type checking when providers
   * and contexts are already loaded (cached).
   */
  #[Bench\Revs(1000)]
  #[Bench\Iterations(20)]
  #[Bench\Warmup(2)]
  #[Bench\RetryThreshold(5)]
  public function benchEnvironmentTypeCheck(): void {
    // Ensure environment is initialized.
    static $initialized = FALSE;
    if (!$initialized) {
      Environment::init();
      $initialized = TRUE;
    }

    // This should be very fast as everything is cached.
    Environment::isDev();
    Environment::isProd();
    Environment::isLocal();
  }

  /**
   * Benchmark custom provider additions with varying counts.
   *
   * This measures the impact of adding custom providers on top of built-in ones.
   * Tests with 1, 2, 5, and 10 additional providers to see scaling performance.
   */
  #[Bench\Revs(20)]
  #[Bench\Iterations(15)]
  #[Bench\Warmup(2)]
  #[Bench\RetryThreshold(5)]
  #[Bench\BeforeMethods(['setUp'])]
  #[Bench\ParamProviders(['provideCustomProviderCounts'])]
  public function benchCustomProviderAdditions(array $params): void {
    $count = $params['count'];
    
    // Add specified number of custom providers.
    static $counter = 0;
    for ($i = 0; $i < $count; $i++) {
      $uniqueId = 'test_provider_' . uniqid() . '_' . (++$counter);
      $provider = new class($uniqueId, 'Test Provider ' . $i) implements ProviderInterface {

        public function __construct(
          private string $id,
          private string $label,
        ) {}

        public function id(): string {
          return $this->id;
        }

        public function label(): string {
          return $this->label;
        }

        public function active(): bool {
          return FALSE;
        }

        public function type(): ?string {
          return NULL;
        }

        public function data(): array {
          return [];
        }

        public function contextualize(ContextInterface $context): void {
          // No-op for benchmark.
        }

      };

      Environment::addProvider($provider);
    }
    
    // Measure the performance with the added providers.
    Environment::providers();
    Environment::provider(); // This will trigger early termination logic.
  }

  /**
   * Benchmark custom context additions with varying counts.
   *
   * This measures the impact of adding custom contexts on top of built-in ones.
   * Tests with 1, 2, 5, and 10 additional contexts to see scaling performance.
   */
  #[Bench\Revs(20)]
  #[Bench\Iterations(15)]
  #[Bench\Warmup(2)]
  #[Bench\RetryThreshold(5)]
  #[Bench\BeforeMethods(['setUp'])]
  #[Bench\ParamProviders(['provideCustomContextCounts'])]
  public function benchCustomContextAdditions(array $params): void {
    $count = $params['count'];
    
    // Add specified number of custom contexts.
    static $counter = 0;
    for ($i = 0; $i < $count; $i++) {
      $uniqueId = 'test_context_' . uniqid() . '_' . (++$counter);
      $context = new class($uniqueId, 'Test Context ' . $i) implements ContextInterface {

        public function __construct(
          private string $id,
          private string $label,
        ) {}

        public function id(): string {
          return $this->id;
        }

        public function label(): string {
          return $this->label;
        }

        public function active(): bool {
          return FALSE;
        }

        public function contextualize(): void {
          // No-op for benchmark.
        }

      };

      Environment::addContext($context);
    }
    
    // Measure the performance with the added contexts.
    Environment::contexts();
    Environment::context(); // This will trigger early termination logic.
  }

  /**
   * Provide parameter sets for custom provider count benchmarks.
   */
  public function provideCustomProviderCounts(): \Generator {
    yield '1 custom provider' => ['count' => 1];
    yield '2 custom providers' => ['count' => 2];
    yield '5 custom providers' => ['count' => 5];
    yield '10 custom providers' => ['count' => 10];
  }

  /**
   * Provide parameter sets for custom context count benchmarks.
   */
  public function provideCustomContextCounts(): \Generator {
    yield '1 custom context' => ['count' => 1];
    yield '2 custom contexts' => ['count' => 2];
    yield '5 custom contexts' => ['count' => 5];
    yield '10 custom contexts' => ['count' => 10];
  }

}
