<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Platforms;

use DrevOps\EnvironmentDetector\Environment;

/**
 * CircleCi platform.
 *
 * Detects the CircleCi environment type.
 *
 * @package DrevOps\EnvironmentDetector\Platforms
 */
class CircleCi extends AbstractPlatform {

  /**
   * {@inheritdoc}
   */
  public const ID = 'circleci';

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
