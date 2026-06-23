<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Benchmarks;

use DrevOps\EnvironmentDetector\Contexts\ContextInterface;
use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Platforms\PlatformInterface;
use DrevOps\EnvironmentDetector\Stacks\StackInterface;
use PhpBench\Attributes as Bench;

class DiscoveryBenchmark {

  public function setUp(): void {
    Environment::reset();
  }

  /**
   * Benchmark registering custom platforms.
   *
   * @param array<string,int> $params
   *   An array of parameters.
   */
  #[Bench\Revs(50)]
  #[Bench\Iterations(20)]
  #[Bench\Warmup(2)]
  #[Bench\RetryThreshold(5)]
  #[Bench\BeforeMethods(['setUp'])]
  #[Bench\ParamProviders(['provideCustomPlatforms'])]
  public function benchCustomPlatforms(array $params): void {
    $platforms = [];
    // Add specified number of custom platforms.
    static $counter = 0;
    for ($i = 0; $i < intval($params['count']); $i++) {
      $unique_id = 'test_platform_' . uniqid() . '_' . (++$counter);
      $platforms[] = new class($unique_id) implements PlatformInterface {

        public function __construct(
          private readonly string $id,
        ) {
        }

        public function id(): string {
          return $this->id;
        }

        public function active(): bool {
          return FALSE;
        }

        public function type(): ?string {
          return NULL;
        }

        public function contextualize(ContextInterface $context): void {
          // No-op for benchmark.
        }

      };
    }

    Environment::init(contextualize: FALSE, platforms: $platforms);
  }

  public function provideCustomPlatforms(): \Generator {
    yield '0 custom platform' => ['count' => 0];
    yield '1 custom platform' => ['count' => 1];
    yield '2 custom platforms' => ['count' => 2];
    yield '5 custom platforms' => ['count' => 5];
    yield '10 custom platforms' => ['count' => 10];
  }

  /**
   * Benchmark registering custom stacks.
   *
   * @param array<string,int> $params
   *   An array of parameters.
   */
  #[Bench\Revs(50)]
  #[Bench\Iterations(20)]
  #[Bench\Warmup(2)]
  #[Bench\RetryThreshold(5)]
  #[Bench\BeforeMethods(['setUp'])]
  #[Bench\ParamProviders(['provideCustomStacks'])]
  public function benchCustomStacks(array $params): void {
    $stacks = [];
    // Add specified number of custom stacks.
    static $counter = 0;
    for ($i = 0; $i < intval($params['count']); $i++) {
      $unique_id = 'test_stack_' . uniqid() . '_' . (++$counter);
      $stacks[] = new class($unique_id) implements StackInterface {

        public function __construct(
          private readonly string $id,
        ) {
        }

        public function id(): string {
          return $this->id;
        }

        public function active(): bool {
          return FALSE;
        }

        public function contextualize(ContextInterface $context): void {
          // No-op for benchmark.
        }

      };
    }

    Environment::init(contextualize: FALSE, stacks: $stacks);
  }

  public function provideCustomStacks(): \Generator {
    yield '0 custom stack' => ['count' => 0];
    yield '1 custom stack' => ['count' => 1];
    yield '2 custom stacks' => ['count' => 2];
    yield '5 custom stacks' => ['count' => 5];
    yield '10 custom stacks' => ['count' => 10];
  }

  /**
   * Benchmark registering custom contexts.
   *
   * @param array<string,int> $params
   *   An array of parameters.
   */
  #[Bench\Revs(50)]
  #[Bench\Iterations(20)]
  #[Bench\Warmup(2)]
  #[Bench\RetryThreshold(5)]
  #[Bench\BeforeMethods(['setUp'])]
  #[Bench\ParamProviders(['provideCustomContexts'])]
  public function benchCustomContexts(array $params): void {
    $contexts = [];
    // Add specified number of custom contexts.
    static $counter = 0;
    for ($i = 0; $i < intval($params['count']); $i++) {
      $unique_id = 'test_context_' . uniqid() . '_' . (++$counter);
      $contexts[] = new class($unique_id) implements ContextInterface {

        public function __construct(
          private readonly string $id,
        ) {
        }

        public function id(): string {
          return $this->id;
        }

        public function active(): bool {
          return FALSE;
        }

        public function contextualize(): void {
          // No-op for benchmark.
        }

      };
    }

    Environment::init(contextualize: FALSE, contexts: $contexts);
  }

  public function provideCustomContexts(): \Generator {
    yield '0 custom context' => ['count' => 0];
    yield '1 custom context' => ['count' => 1];
    yield '2 custom contexts' => ['count' => 2];
    yield '5 custom contexts' => ['count' => 5];
    yield '10 custom contexts' => ['count' => 10];
  }

}
