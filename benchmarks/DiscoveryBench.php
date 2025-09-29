<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Benchmarks;

use DrevOps\EnvironmentDetector\Providers\ProviderInterface;
use DrevOps\EnvironmentDetector\Contexts\ContextInterface;
use DrevOps\EnvironmentDetector\Environment;
use PhpBench\Attributes as Bench;

/**
 * Benchmark for provider and context discovery performance.
 *
 * This benchmark measures the performance impact of scanning the filesystem
 * for providers and contexts using scandir().
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
   * Benchmark provider discovery from filesystem.
   *
   * This measures the time it takes to scan and load all providers
   * from the filesystem using scandir().
   */
  #[Bench\Revs(100)]
  #[Bench\Iterations(25)]
  #[Bench\Warmup(2)]
  #[Bench\RetryThreshold(5)]
  #[Bench\BeforeMethods(['setUp'])]
  public function benchProviderDiscovery(): void {
    // This will trigger filesystem scanning and provider instantiation.
    Environment::providers();
  }

  /**
   * Benchmark context discovery from filesystem.
   *
   * This measures the time it takes to scan and load all contexts
   * from the filesystem using scandir().
   */
  #[Bench\Revs(100)]
  #[Bench\Iterations(25)]
  #[Bench\Warmup(2)]
  #[Bench\RetryThreshold(5)]
  #[Bench\BeforeMethods(['setUp'])]
  public function benchContextDiscovery(): void {
    // This will trigger filesystem scanning and context instantiation.
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
   * Benchmark multiple provider registrations.
   *
   * This measures the impact of adding multiple custom providers.
   */
  #[Bench\Revs(10)]
  #[Bench\Iterations(15)]
  #[Bench\Warmup(1)]
  #[Bench\RetryThreshold(10)]
  #[Bench\BeforeMethods(['setUp'])]
  public function benchMultipleProviderRegistrations(): void {
    // Create dummy providers to test registration performance.
    // Use unique IDs based on microtime to avoid conflicts across revisions.
    static $counter = 0;
    for ($i = 0; $i < 5; $i++) {
      $uniqueId = 'test_' . uniqid() . '_' . (++$counter);
      $provider = new class($uniqueId, 'Test Provider ' . $i) implements ProviderInterface {

        public function __construct(
          private string $id,
          private string $label,
        ) {}

        /**
         * {@inheritdoc}
         */
        public function id(): string {
          return $this->id;
        }

        /**
         * {@inheritdoc}
         */
        public function label(): string {
          return $this->label;
        }

        /**
         * {@inheritdoc}
         */
        public function active(): bool {
          return FALSE;
        }

        /**
         * {@inheritdoc}
         */
        public function type(): ?string {
          return NULL;
        }

        /**
         * {@inheritdoc}
         */
        public function data(): array {
          return [];
        }

        /**
         * {@inheritdoc}
         */
        public function contextualize(ContextInterface $context): void {
          // No-op for benchmark.
        }

      };

      Environment::addProvider($provider);
    }
  }

}
