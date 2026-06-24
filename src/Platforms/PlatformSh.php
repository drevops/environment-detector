<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Platforms;

use DrevOps\EnvironmentDetector\Environment;

/**
 * Platform.sh hosting platform.
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
    $env_type = getenv('PLATFORM_ENVIRONMENT_TYPE');

    if ($env_type === 'production') {
      return Environment::PRODUCTION;
    }

    if ($env_type === 'staging') {
      return Environment::STAGE;
    }

    // Beyond production and staging, an environment not typed 'development' is
    // not one of the persistent tiers, so it is an ephemeral preview. Only the
    // development branch is the development tier.
    if ($env_type !== 'development') {
      return Environment::PREVIEW;
    }

    if (getenv('PLATFORM_BRANCH') === static::DEVELOPMENT_BRANCH) {
      return Environment::DEVELOPMENT;
    }

    // Any other branch is an ephemeral per-branch build - a preview.
    return Environment::PREVIEW;
  }

}
