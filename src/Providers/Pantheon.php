<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Providers;

use DrevOps\EnvironmentDetector\Environment;

/**
 * Pantheon provider.
 *
 * Detects the Pantheon environment type.
 *
 * @package DrevOps\EnvironmentDetector\Providers
 */
class Pantheon extends AbstractProvider {

  /**
   * {@inheritdoc}
   */
  public const string ID = 'pantheon';

  /**
   * {@inheritdoc}
   */
  public const string LABEL = 'Pantheon';

  /**
   * {@inheritdoc}
   */
  protected static function envPrefixes(): array {
    return ['PANTHEON_', 'TERMINUS_'];
  }

  /**
   * {@inheritdoc}
   */
  public function active(): bool {
    // Some local development environments may inject the PANTHEON_ENVIRONMENT.
    // @see https://docs.lando.dev/plugins/pantheon/v/v1.8.0/environment.html
    return getenv('PANTHEON_ENVIRONMENT') !== FALSE && !in_array(getenv('PANTHEON_ENVIRONMENT'), ['ddev', 'lando']);
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
