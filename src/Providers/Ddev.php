<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Providers;

use DrevOps\EnvironmentDetector\Environment;

/**
 * DDEV provider.
 *
 * Detects the DDEV environment type.
 *
 * @package DrevOps\EnvironmentDetector\Providers
 */
class Ddev extends AbstractProvider {

  /**
   * {@inheritdoc}
   */
  public const string ID = 'ddev';

  /**
   * {@inheritdoc}
   */
  public const string LABEL = 'DDEV';

  /**
   * {@inheritdoc}
   */
  protected static function envPrefixes(): array {
    return ['DDEV_', 'IS_DDEV_PROJECT'];
  }

  /**
   * {@inheritdoc}
   */
  public function active(): bool {
    return getenv('IS_DDEV_PROJECT') !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function type(): ?string {
    // DDEV may run in CI.
    return getenv('CI') ? Environment::CI : Environment::LOCAL;
  }

}
