<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Benchmarks;

use DrevOps\EnvironmentDetector\Contexts\ContextInterface;
use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Platforms\PlatformInterface;
use DrevOps\EnvironmentDetector\Stacks\StackInterface;
use PhpBench\Attributes as Bench;

/**
 * Benchmarks how registering extra rings scales the cold detection cost.
 *
 * Every subject resets the detector at the start of each revolution, so each
 * measurement registers the additional rings and runs a full discovery rather
 * than rebuilding the input and returning early from a warm init().
 *
 * @package DrevOps\EnvironmentDetector\Benchmarks
 */
class DiscoveryBenchmark extends AbstractBenchmark {

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
  #[Bench\ParamProviders(['provideCustomPlatforms'])]
  public function benchCustomPlatforms(array $params): void {
    $this->resetDetector();

    $platforms = [];
    for ($i = 0; $i < intval($params['count']); $i++) {
      $platforms[] = new class('test_platform_' . $i) implements PlatformInterface {

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
  #[Bench\ParamProviders(['provideCustomStacks'])]
  public function benchCustomStacks(array $params): void {
    $this->resetDetector();

    $stacks = [];
    for ($i = 0; $i < intval($params['count']); $i++) {
      $stacks[] = new class('test_stack_' . $i) implements StackInterface {

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
  #[Bench\ParamProviders(['provideCustomContexts'])]
  public function benchCustomContexts(array $params): void {
    $this->resetDetector();

    $contexts = [];
    for ($i = 0; $i < intval($params['count']); $i++) {
      $contexts[] = new class('test_context_' . $i) implements ContextInterface {

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
