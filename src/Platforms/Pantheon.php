<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Platforms;

use DrevOps\EnvironmentDetector\Environment;

/**
 * Pantheon hosting platform.
 *
 * @package DrevOps\EnvironmentDetector\Platforms
 */
class Pantheon extends AbstractPlatform {

  /**
   * {@inheritdoc}
   */
  public const ID = 'pantheon';

  /**
   * {@inheritdoc}
   */
  public function active(): bool {
    // Some local development environments may inject the PANTHEON_ENVIRONMENT.
    // @see https://docs.lando.dev/plugins/pantheon/v/v1.8.0/environment.html
    return getenv('PANTHEON_ENVIRONMENT') !== FALSE && !in_array(getenv('PANTHEON_ENVIRONMENT'), ['ddev', 'lando'], TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function type(): ?string {
    // @todo Review this implementation as some implementations may consider
    // 'dev', 'test' and 'live' as being the 'production' environment from the
    // perspective of the application.
    return match (getenv('PANTHEON_ENVIRONMENT')) {
      'dev' => Environment::DEVELOPMENT,
      'test' => Environment::STAGE,
      'live' => Environment::PRODUCTION,
      default => Environment::PREVIEW,
    };
  }

}
