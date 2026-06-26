<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Benchmarks;

use DrevOps\EnvironmentDetector\Environment;

/**
 * Base class for environment detector benchmarks.
 *
 * @package DrevOps\EnvironmentDetector\Benchmarks
 */
abstract class AbstractBenchmark {

  /**
   * Reset the detector to its pre-detection state.
   *
   * Clears the cached type as well as the detector statics so the next init()
   * performs a full cold detection instead of returning early. Without clearing
   * the env var, discoverType() would short-circuit on the value a previous
   * revolution wrote and the measured path would collapse to a no-op.
   */
  protected function resetDetector(): void {
    Environment::reset();
    putenv('ENVIRONMENT_TYPE');
    unset($_ENV['ENVIRONMENT_TYPE'], $_SERVER['ENVIRONMENT_TYPE']);
  }

}
