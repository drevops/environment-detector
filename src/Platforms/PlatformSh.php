<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Platforms;

use DrevOps\EnvironmentDetector\Environment;

/**
 * Platform.sh platform.
 *
 * Detects the Platform.sh environment type.
 *
 * @package DrevOps\EnvironmentDetector\Platforms
 */
class PlatformSh extends AbstractPlatform {

  /**
   * {@inheritdoc}
   */
  public const ID = 'platformsh';

  /**
   * {@inheritdoc}
   */
  public function active(): bool {
    return getenv('PLATFORM_ENVIRONMENT_TYPE') !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function type(): ?string {
    return match (getenv('PLATFORM_ENVIRONMENT_TYPE')) {
      'development' => Environment::DEVELOPMENT,
      'staging' => Environment::STAGE,
      'production' => Environment::PRODUCTION,
      default => NULL,
    };
  }

}
