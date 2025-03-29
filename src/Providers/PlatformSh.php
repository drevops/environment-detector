<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Providers;

use DrevOps\EnvironmentDetector\Environment;

/**
 * Platform.sh provider.
 *
 * Detects the Platform.sh environment type.
 *
 * @package DrevOps\EnvironmentDetector\Providers
 */
class PlatformSh extends AbstractProvider {

  /**
   * {@inheritdoc}
   */
  public const string ID = 'platformsh';

  /**
   * {@inheritdoc}
   */
  public const string LABEL = 'Platform.sh';

  /**
   * {@inheritdoc}
   */
  protected static function envPrefixes(): array {
    return ['PLATFORM_'];
  }

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
