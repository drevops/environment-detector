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
   * Every env var a built-in platform or stack reads to detect itself.
   *
   * Benchmarks run wherever CI happens to place them - and a CI runner sets its
   * own platform signals (GitHub Actions exports GITHUB_WORKFLOW, etc.). Left in
   * place, those would activate a second platform and make detection
   * non-deterministic or even collide with the scenario a benchmark sets up. The
   * environment is cleared to this known baseline before each scenario so a run
   * measures the same thing on every host.
   *
   * @var string[]
   */
  protected const SIGNAL_ENV_VARS = [
    'CI',
    'container',
    'AH_SITE_ENVIRONMENT',
    'AH_SITE_GROUP',
    'CIRCLECI',
    'DOCKER',
    'DRUPAL_ACQUIA_SETTINGS_FILE',
    'DRUPAL_CONFIG_PATH',
    'DRUPAL_TMP_PATH',
    'DRUPAL_TMP_PATH_IS_SHARED',
    'ENVIRONMENT_PRODUCTION_BRANCH',
    'GITHUB_WORKFLOW',
    'GITLAB_CI',
    'IS_DDEV_PROJECT',
    'LAGOON_ENVIRONMENT_TYPE',
    'LAGOON_GIT_BRANCH',
    'LAGOON_GIT_SAFE_BRANCH',
    'LAGOON_KUBERNETES',
    'LAGOON_PROJECT',
    'LAGOON_ROUTES',
    'LANDO_INFO',
    'LOCALDEV_URL',
    'PANTHEON_ENVIRONMENT',
    'PLATFORM_BRANCH',
    'PLATFORM_ENVIRONMENT_TYPE',
    'SERVICE_HOSTS',
    'SKPR_ENV',
    'TUGBOAT_PREVIEW_ID',
  ];

  /**
   * Clear the ambient environment so a benchmark controls its own scenario.
   *
   * Run once per iteration before any scenario env vars are set, so the host's
   * own platform/stack signals never leak into the measurement.
   */
  protected function neutralizeEnvironment(): void {
    putenv('ENVIRONMENT_TYPE');
    unset($_ENV['ENVIRONMENT_TYPE'], $_SERVER['ENVIRONMENT_TYPE']);

    foreach (static::SIGNAL_ENV_VARS as $name) {
      putenv($name);
      unset($_ENV[$name], $_SERVER[$name]);
    }
  }

  /**
   * Reset the detector and cached type so the next init() detects from scratch.
   *
   * Run at the start of every measured revolution; scenario env vars set for the
   * iteration are left intact so each revolution re-detects the same scenario.
   */
  protected function resetDetector(): void {
    Environment::reset();
    putenv('ENVIRONMENT_TYPE');
    unset($_ENV['ENVIRONMENT_TYPE'], $_SERVER['ENVIRONMENT_TYPE']);
  }

}
