<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Contexts;

/**
 * Drupal application context.
 *
 * Holds the site's $settings and $config by reference, so settings written
 * during contextualization land in the very arrays the framework reads back
 * rather than in a separate global copy. Callers pass the framework's own
 * variables into the constructor.
 *
 * @package DrevOps\EnvironmentDetector\Contexts
 */
class Drupal extends AbstractContext {

  /**
   * {@inheritdoc}
   */
  public const ID = 'drupal';

  /**
   * The site's settings, held by reference.
   *
   * Values are intentionally untyped: a Drupal settings array is a
   * heterogeneous bag of strings, booleans, and nested arrays.
   */
  public array $settings;

  /**
   * The site's config, held by reference.
   */
  public array $config;

  /**
   * Constructor.
   *
   * @param array $settings
   *   The site's settings array, bound by reference.
   * @param array $config
   *   The site's config array, bound by reference.
   */
  public function __construct(array &$settings = [], array &$config = []) {
    $this->settings = &$settings;
    $this->config = &$config;
  }

  /**
   * {@inheritdoc}
   */
  public function active(): bool {
    // A populated hash_salt (settings.php) or a site UUID (installed config)
    // only exist once Drupal has bootstrapped, so either one signals Drupal.
    return !empty($this->settings['hash_salt']) || !empty($this->config['system.site']['uuid']);
  }

  /**
   * {@inheritdoc}
   */
  public function contextualize(): void {
    $this->settings['environment'] = getenv('ENVIRONMENT_TYPE');

    // The loopback set is the canonical local-development trusted host and is
    // reachable across every stack, so it belongs in the always-applied context
    // rather than any single substrate.
    $this->settings['trusted_host_patterns'][] = '^localhost$';
    $this->settings['trusted_host_patterns'][] = '^127\.0\.0\.1$';
  }

}
