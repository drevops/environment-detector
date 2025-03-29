<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Providers;

use DrevOps\EnvironmentDetector\Environment;

/**
 * CircleCi provider.
 *
 * Detects the CircleCi environment type.
 *
 * @package DrevOps\EnvironmentDetector\Providers
 */
class CircleCi extends AbstractProvider {

  /**
   * {@inheritdoc}
   */
  public const string ID = 'circleci';

  /**
   * {@inheritdoc}
   */
  public const string LABEL = 'CircleCI';

  /**
   * {@inheritdoc}
   */
  protected static function envPrefixes(): array {
    return ['CIRCLECI', 'CIRCLE_'];
  }

  /**
   * {@inheritdoc}
   */
  public function active(): bool {
    return getenv('CIRCLECI') !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function type(): ?string {
    return Environment::CI;
  }

}
