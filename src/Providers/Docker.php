<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Providers;

use DrevOps\EnvironmentDetector\Environment;

/**
 * Docker (vanilla) provider.
 *
 * Detects the DDEV environment type.
 *
 * @package DrevOps\EnvironmentDetector\Providers
 */
class Docker extends AbstractProvider {

  /**
   * {@inheritdoc}
   */
  public const string ID = 'docker';

  /**
   * {@inheritdoc}
   */
  public const string LABEL = 'Docker';

  /**
   * {@inheritdoc}
   */
  protected function envPrefixes(): array {
    return ['DOCKER', 'DOCKER_'];
  }

  /**
   * {@inheritdoc}
   */
  public function active(): bool {
    if (!empty(getenv('IS_DDEV_PROJECT')) || !empty(getenv('LANDO_INFO'))) {
      return FALSE;
    }

    if (getenv('DOCKER') !== FALSE) {
      return TRUE;
    }

    if (getenv('container') !== FALSE) {
      return TRUE;
    }

    // @codeCoverageIgnoreStart
    if (file_exists('/.dockerenv') || file_exists('/.dockerinit')) {
      return TRUE;
    }

    $cgroup = @file_get_contents('/proc/1/cgroup');
    // @codeCoverageIgnoreEnd
    return $cgroup && (str_contains($cgroup, 'docker') || str_contains($cgroup, 'kubepods'));
  }

  /**
   * {@inheritdoc}
   */
  public function type(): ?string {
    // Docker may run in CI.
    return getenv('CI') ? Environment::CI : Environment::LOCAL;
  }

}
