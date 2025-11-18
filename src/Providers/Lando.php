<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Providers;

use DrevOps\EnvironmentDetector\Environment;

/**
 * Lando provider.
 *
 * Detects the Lando environment type.
 *
 * @package DrevOps\EnvironmentDetector\Providers
 */
class Lando extends AbstractProvider {

  /**
   * {@inheritdoc}
   */
  public const ID = 'lando';

  /**
   * {@inheritdoc}
   */
  public const LABEL = 'Lando';

  /**
   * {@inheritdoc}
   */
  protected function envPrefixes(): array {
    return ['LANDO_'];
  }

  /**
   * {@inheritdoc}
   */
  public function active(): bool {
    return getenv('LANDO_INFO') !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function type(): ?string {
    return Environment::LOCAL;
  }

}
