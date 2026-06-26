<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Stacks;

use DrevOps\EnvironmentDetector\Contexts\Drupal;
use DrevOps\EnvironmentDetector\Contexts\DrupalContextualizerInterface;

/**
 * Container stack (generic containerisation).
 *
 * @package DrevOps\EnvironmentDetector\Stacks
 */
class Container extends AbstractStack implements DrupalContextualizerInterface {

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
   * Cached result of the container probe, shared across the run.
   */
  protected static ?bool $cachedIsContainer = NULL;

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
    // Containerisation is fixed for the lifetime of the process, so the probe
    // is computed once per run and the result reused on any later call. A
    // subclass overriding isContainer() opts out of the cache and is probed on
    // its own terms.
    return self::$cachedIsContainer ??= $this->detectContainer();
  }

  /**
   * Reset the cached container probe so the next detection re-runs it.
   */
  public static function resetCache(): void {
    self::$cachedIsContainer = NULL;
  }

  /**
   * Probe the host for the signals that indicate a container.
   *
   * @return bool
   *   TRUE if running inside a container, FALSE otherwise.
   */
  protected function detectContainer(): bool {
    // No single marker reliably proves containerisation across runtimes, so
    // several independent signals are probed in turn until one matches.
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
  public function contextualizeDrupal(Drupal $context): void {
    $settings = &$context->settings;

    // Internal service hostnames, reachable container-to-container. The
    // SERVICE_HOSTS env var contributes extra comma-separated hosts on top of
    // the built-in allowlist. Every host is escaped before joining the
    // alternation: the pattern feeds the security-sensitive
    // trusted_host_patterns, so a host carrying a regex metacharacter must not
    // be able to widen the match.
    $hosts = static::SERVICE_HOSTS;

    $extra = getenv('SERVICE_HOSTS');
    if (is_string($extra)) {
      foreach (explode(',', $extra) as $host) {
        $host = trim($host);

        if ($host !== '') {
          $hosts[] = $host;
        }
      }
    }

    $hosts = array_map(static fn(string $host): string => preg_quote($host, '#'), $hosts);
    $settings['trusted_host_patterns'][] = '^(' . implode('|', array_unique($hosts)) . ')$';

    // The site's local development URL, reduced to its host: a port, path, or
    // credentials would never match Drupal's host-only trusted-host check.
    $url = getenv('LOCALDEV_URL');
    if (is_string($url) && $url !== '') {
      $host = parse_url($url, PHP_URL_HOST);
      if (!is_string($host) || $host === '') {
        // A bare host with no scheme parses as a path, so retry with one.
        $host = parse_url('http://' . ltrim($url, '/'), PHP_URL_HOST);
      }
      if (is_string($host) && $host !== '') {
        $settings['trusted_host_patterns'][] = '^' . preg_quote($host, '#') . '$';
      }
    }
  }

}
