<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Providers;

use DrevOps\EnvironmentDetector\Environment;

/**
 * Acquia Cloud provider.
 *
 * Detects the Acquia Cloud environment type.
 *
 * @package DrevOps\EnvironmentDetector\Providers
 */
class Acquia extends AbstractProvider {

  /**
   * {@inheritdoc}
   */
  public const ID = 'acquia';

  /**
   * {@inheritdoc}
   */
  public const LABEL = 'Acquia Cloud';

  /**
   * {@inheritdoc}
   */
  protected function envPrefixes(): array {
    return ['AH_'];
  }

  /**
   * {@inheritdoc}
   */
  public function active(): bool {
    return getenv('AH_SITE_ENVIRONMENT') !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function type(): ?string {
    return match (getenv('AH_SITE_ENVIRONMENT')) {
      'dev' => Environment::DEVELOPMENT,
      'test' => Environment::STAGE,
      'prod' => Environment::PRODUCTION,
      default => NULL,
    };
  }

}
