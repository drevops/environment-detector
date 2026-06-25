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
   * Conventional internal service hostnames used across Docker Compose stacks.
   *
   * @var string[]
   */
  public const SERVICE_HOSTS = ['web', 'app', 'webserver', 'nginx', 'apache', 'apache2'];

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

    $settings = &$context->settings;

    // Internal service hostnames, reachable container-to-container.
    $settings['trusted_host_patterns'][] = '^(' . implode('|', static::SERVICE_HOSTS) . ')$';

    // The site's local development URL, reduced to its host: a port or path
    // would never match Drupal's host-only trusted-host check.
    $url = getenv('LOCALDEV_URL');
    if (is_string($url) && $url !== '') {
      $host = preg_replace('#^https?://#', '', $url);
      $host = preg_replace('#[/:].*$#', '', (string) $host);
      $settings['trusted_host_patterns'][] = '^' . str_replace('.', '\.', (string) $host) . '$';
    }
  }

}
