<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Contexts;

/**
 * Drupal context.
 *
 * Detects the Drupal context.
 *
 * @package DrevOps\EnvironmentDetector\Contexts
 */
class Drupal extends AbstractContext {

  /**
   * {@inheritdoc}
   */
  public const string ID = 'drupal';

  /**
   * {@inheritdoc}
   */
  public const string LABEL = 'Drupal';

  /**
   * {@inheritdoc}
   */
  public function active(): bool {
    global $settings;
    global $config;

    return !empty($settings['hash_salt']) || !empty($config['system.site']['uuid']);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function contextualize(): void {
    global $settings;
    $settings['environment'] = getenv('ENVIRONMENT_TYPE');
  }

}
