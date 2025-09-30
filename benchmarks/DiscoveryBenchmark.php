<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Benchmarks;

use DrevOps\EnvironmentDetector\Contexts\ContextInterface;
use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Providers\ProviderInterface;
use PhpBench\Attributes as Bench;

class DiscoveryBenchmark {

  public function setUp(): void {
    Environment::reset();
  }

  /**
   * Data provider for the `benchAddProvider` benchmark.
   *
   * @param array<string,int> $params
   *   An array of parameters.
   */
  #[Bench\Revs(50)]
  #[Bench\Iterations(20)]
  #[Bench\Warmup(2)]
  #[Bench\RetryThreshold(5)]
  #[Bench\BeforeMethods(['setUp'])]
  #[Bench\ParamProviders(['provideAddProvider'])]
  public function benchAddProvider(array $params): void {
    // Initialize providers to ensure built-in ones are loaded first.
    Environment::providers();

    // Add specified number of custom providers.
    static $counter = 0;
    for ($i = 0; $i < intval($params['count']); $i++) {
      $uniqueId = 'test_provider_' . uniqid() . '_' . (++$counter);
      $provider = new class($uniqueId, 'Test Provider ' . $i) implements ProviderInterface {

        public function __construct(
          private string $id,
          private string $label,
        ) {
        }

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

    // Access providers to ensure they are loaded.
    Environment::provider();
    Environment::type();
  }

  public function provideAddProvider(): \Generator {
    yield '0 custom provider' => ['count' => 0];
    yield '1 custom provider' => ['count' => 1];
    yield '2 custom providers' => ['count' => 2];
    yield '5 custom providers' => ['count' => 5];
    yield '10 custom providers' => ['count' => 10];
  }

  /**
   * Data provider for the `benchAddContext` benchmark.
   *
   * @param array<string,int> $params
   *   An array of parameters.
   */
  #[Bench\Revs(50)]
  #[Bench\Iterations(20)]
  #[Bench\Warmup(2)]
  #[Bench\RetryThreshold(5)]
  #[Bench\BeforeMethods(['setUp'])]
  #[Bench\ParamProviders(['provideAddContext'])]
  public function benchAddContext(array $params): void {
    // Initialize contexts to ensure built-in ones are loaded first.
    Environment::contexts();

    // Add specified number of custom contexts.
    static $counter = 0;
    for ($i = 0; $i < intval($params['count']); $i++) {
      $uniqueId = 'test_context_' . uniqid() . '_' . (++$counter);
      $context = new class($uniqueId, 'Test Context ' . $i) implements ContextInterface {

        public function __construct(
          private string $id,
          private string $label,
        ) {
        }

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

    // Access contexts to ensure they are loaded.
    Environment::context();
  }

  public function provideAddContext(): \Generator {
    yield '0 custom context' => ['count' => 0];
    yield '1 custom context' => ['count' => 1];
    yield '2 custom contexts' => ['count' => 2];
    yield '5 custom contexts' => ['count' => 5];
    yield '10 custom contexts' => ['count' => 10];
  }

}
