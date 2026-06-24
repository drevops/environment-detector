<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Platforms;

use DrevOps\EnvironmentDetector\Environment;

/**
 * CircleCI continuous integration platform.
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
