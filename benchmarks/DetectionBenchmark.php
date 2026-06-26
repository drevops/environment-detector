<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Benchmarks;

use DrevOps\EnvironmentDetector\Contexts\Drupal;
use DrevOps\EnvironmentDetector\Environment;
use PhpBench\Attributes as Bench;

/**
 * Benchmarks the real cold detection and contextualization paths.
 *
 * Every subject resets the detector at the start of each revolution, so the
 * full once-per-request cost - discovery, type resolution and settings
 * application - is measured rather than the early return a warm init() takes.
 *
 * @package DrevOps\EnvironmentDetector\Benchmarks
 */
class DetectionBenchmark extends AbstractBenchmark {

  /**
   * Mark the run as containerised for the container-stack subjects.
   */
  public function setUpContainer(): void {
    putenv('DOCKER=TRUE');
  }

  /**
   * Activate the Lagoon platform with a resolvable production tier.
   */
  public function setUpLagoon(): void {
    putenv('LAGOON_KUBERNETES=remote');
    putenv('LAGOON_ENVIRONMENT_TYPE=production');
    putenv('LAGOON_PROJECT=example');
    putenv('LAGOON_GIT_SAFE_BRANCH=main');
    putenv('LAGOON_ROUTES=https://example.com,https://www.example.com');
  }

  /**
   * Activate the Lagoon platform inside a container.
   */
  public function setUpLagoonContainer(): void {
    $this->setUpContainer();
    $this->setUpLagoon();
  }

  /**
   * Benchmark a cold local detection with no platform and no context.
   */
  #[Bench\Revs(50)]
  #[Bench\Iterations(20)]
  #[Bench\Warmup(2)]
  #[Bench\RetryThreshold(5)]
  public function benchDetectLocal(): void {
    $this->resetDetector();

    Environment::init();
  }

  /**
   * Benchmark a cold detection that contextualizes Drupal on the native host.
   */
  #[Bench\Revs(50)]
  #[Bench\Iterations(20)]
  #[Bench\Warmup(2)]
  #[Bench\RetryThreshold(5)]
  public function benchDetectDrupalNative(): void {
    $this->resetDetector();

    $settings = ['hash_salt' => 'benchmark'];
    $config = [];

    Environment::init(contexts: [new Drupal($settings, $config)]);
  }

  /**
   * Benchmark a cold detection that contextualizes Drupal inside a container.
   */
  #[Bench\Revs(50)]
  #[Bench\Iterations(20)]
  #[Bench\Warmup(2)]
  #[Bench\RetryThreshold(5)]
  #[Bench\BeforeMethods(['setUpContainer'])]
  public function benchDetectDrupalContainer(): void {
    $this->resetDetector();

    $settings = ['hash_salt' => 'benchmark'];
    $config = [];

    Environment::init(contexts: [new Drupal($settings, $config)]);
  }

  /**
   * Benchmark a cold detection of an active platform resolving its type.
   */
  #[Bench\Revs(50)]
  #[Bench\Iterations(20)]
  #[Bench\Warmup(2)]
  #[Bench\RetryThreshold(5)]
  #[Bench\BeforeMethods(['setUpLagoon'])]
  public function benchDetectPlatform(): void {
    $this->resetDetector();

    Environment::init();
  }

  /**
   * Benchmark the maximal path: active platform, container stack and context.
   */
  #[Bench\Revs(50)]
  #[Bench\Iterations(20)]
  #[Bench\Warmup(2)]
  #[Bench\RetryThreshold(5)]
  #[Bench\BeforeMethods(['setUpLagoonContainer'])]
  public function benchDetectFullStack(): void {
    $this->resetDetector();

    $settings = ['hash_salt' => 'benchmark'];
    $config = [];

    Environment::init(contexts: [new Drupal($settings, $config)]);
  }

}
