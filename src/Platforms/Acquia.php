<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Platforms;

use DrevOps\EnvironmentDetector\Environment;

/**
 * Acquia Cloud hosting platform.
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
    $env = getenv('AH_SITE_ENVIRONMENT');

    // On-demand (Continuous Delivery) environments are ephemeral previews
    // whose names are not fixed, but Acquia prefixes them with 'ode'.
    if (is_string($env) && str_starts_with($env, 'ode')) {
      return Environment::PREVIEW;
    }

    return match ($env) {
      'dev' => Environment::DEVELOPMENT,
      'stage', 'test' => Environment::STAGE,
      'prod' => Environment::PRODUCTION,
      default => NULL,
    };
  }

}
