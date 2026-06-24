<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Contexts;

/**
 * Drupal application context.
 *
 * @package DrevOps\EnvironmentDetector\Contexts
 */
class Drupal extends AbstractContext {

  /**
   * {@inheritdoc}
   */
  public const ID = 'drupal';

  /**
   * {@inheritdoc}
   */
  public function active(): bool {
    global $settings;
    global $config;

    // A populated hash_salt (settings.php) or a site UUID (installed config)
    // only exist once Drupal has bootstrapped, so either one signals Drupal.
    return !empty($settings['hash_salt']) || !empty($config['system.site']['uuid']);
  }

  /**
   * {@inheritdoc}
   */
  public function contextualize(): void {
    global $settings;
    $settings['environment'] = getenv('ENVIRONMENT_TYPE');
  }

}
