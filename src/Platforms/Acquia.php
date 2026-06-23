<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Platforms;

use DrevOps\EnvironmentDetector\Environment;

/**
 * Acquia Cloud platform.
 *
 * Detects the Acquia Cloud environment type.
 *
 * @package DrevOps\EnvironmentDetector\Platforms
 */
class Acquia extends AbstractPlatform {

  /**
   * {@inheritdoc}
   */
  public const ID = 'acquia';

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
