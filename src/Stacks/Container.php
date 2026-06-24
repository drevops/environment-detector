<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Stacks;

use DrevOps\EnvironmentDetector\Contexts\ContextInterface;
use DrevOps\EnvironmentDetector\Contexts\Drupal;

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
    return $this->isContainer();
  }

  /**
   * Check whether the environment runs inside a container.
   *
   * @return bool
   *   TRUE if running inside a container, FALSE otherwise.
   */
  public function isContainer(): bool {
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

  /**
   * {@inheritdoc}
   */
  public function contextualize(ContextInterface $context): void {
    if (!$context instanceof Drupal) {
      return;
    }

    global $settings;

    // Build a trusted host pattern from a comma-separated list of local
    // development hostnames or URLs (the dev domain plus any internal service
    // names). Mirrors the LAGOON_ROUTES handling.
    $hosts = getenv('DRUPAL_DEV_TRUSTED_HOSTS');
    if (is_string($hosts) && $hosts !== '') {
      $patterns = str_replace(['.', 'https://', 'http://', ','], [
        '\.', '', '', '|',
      ], $hosts);
      $settings['trusted_host_patterns'][] = '^(' . $patterns . ')$';
    }
  }

}
