<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Stacks;

/**
 * Docker (vanilla) stack.
 *
 * Detects whether the environment runs inside a Docker container.
 *
 * @package DrevOps\EnvironmentDetector\Stacks
 */
class Docker extends AbstractStack {

  /**
   * {@inheritdoc}
   */
  public const ID = 'docker';

  /**
   * {@inheritdoc}
   */
  public function active(): bool {
    // No single marker reliably proves containerisation across runtimes, so
    // probe several independent signals in turn: env vars set by tooling, the
    // engine-created marker files, then the control group of PID 1.
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

}
