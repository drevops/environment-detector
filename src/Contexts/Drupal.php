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
  public function active(?array $data = NULL): bool {
    return
      is_array($data)
      && isset($data['settings'])
      && isset($data['config'])
      && (
        !empty($data['settings']['hash_salt'])
        ||
        !empty($data['config']['system.site']['uuid'])
      );
  }

}
