<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Stacks;

/**
 * Container stack (generic containerisation).
 *
 * @package DrevOps\EnvironmentDetector\Stacks
 */
class Container extends AbstractStack {

  /**
   * {@inheritdoc}
   */
  public const ID = 'container';

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

    $cgroup = '';
    if (is_readable('/proc/1/cgroup')) {
      $content = file_get_contents('/proc/1/cgroup');
      $cgroup = is_string($content) ? $content : '';
    }
    // @codeCoverageIgnoreEnd
    return str_contains($cgroup, 'docker') || str_contains($cgroup, 'kubepods');
  }

}
