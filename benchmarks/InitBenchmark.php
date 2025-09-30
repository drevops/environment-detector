<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Benchmarks;

use DrevOps\EnvironmentDetector\Environment;
use PhpBench\Attributes as Bench;

class InitBenchmark {

  public function setUp(): void {
    Environment::reset();
    putenv('ENVIRONMENT_TYPE');
    unset($_ENV['ENVIRONMENT_TYPE'], $_SERVER['ENVIRONMENT_TYPE']);
  }

  /**
   * Data provider for the `provideInitRepeated` benchmark.
   *
   * @param array<string,int|bool> $params
   *   An array of parameters.
   */
  #[Bench\Revs(50)]
  #[Bench\Iterations(20)]
  #[Bench\Warmup(2)]
  #[Bench\RetryThreshold(5)]
  #[Bench\BeforeMethods(['setUp'])]
  #[Bench\ParamProviders(['provideIsAfterInit'])]
  public function benchIsAfterInit(array $params): void {
    Environment::init((bool) $params['contextualize']);

    for ($i = 0; $i < intval($params['count']); $i++) {
      Environment::isProd();
    }
  }

  public function provideIsAfterInit(): \Generator {
    yield '0, F' => ['count' => 0, 'contextualize' => FALSE];
    yield '1, F' => ['count' => 1, 'contextualize' => FALSE];
    yield '2, F' => ['count' => 2, 'contextualize' => FALSE];
    yield '3, F' => ['count' => 3, 'contextualize' => FALSE];
    yield '4, F' => ['count' => 4, 'contextualize' => FALSE];
    yield '5, F' => ['count' => 5, 'contextualize' => FALSE];
    yield '10, F' => ['count' => 10, 'contextualize' => FALSE];
    yield '100, F' => ['count' => 100, 'contextualize' => FALSE];
    yield '1000, F' => ['count' => 1000, 'contextualize' => FALSE];
    yield '0, T' => ['count' => 0, 'contextualize' => TRUE];
    yield '1, T' => ['count' => 1, 'contextualize' => TRUE];
    yield '2, T' => ['count' => 2, 'contextualize' => TRUE];
    yield '3, T' => ['count' => 3, 'contextualize' => TRUE];
    yield '4, T' => ['count' => 4, 'contextualize' => TRUE];
    yield '5, T' => ['count' => 5, 'contextualize' => TRUE];
    yield '10, T' => ['count' => 10, 'contextualize' => TRUE];
    yield '100, T' => ['count' => 100, 'contextualize' => TRUE];
    yield '1000, T' => ['count' => 1000, 'contextualize' => TRUE];
  }

}
