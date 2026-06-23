<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Platforms;

use DrevOps\EnvironmentDetector\Environment;

/**
 * Tugboat platform.
 *
 * Detects the Tugboat environment type.
 *
 * @package DrevOps\EnvironmentDetector\Platforms
 */
class Tugboat extends AbstractPlatform {

  /**
   * {@inheritdoc}
   */
  public const ID = 'tugboat';

  /**
   * {@inheritdoc}
   */
  public function active(): bool {
    return getenv('TUGBOAT_PREVIEW_ID') !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function type(): ?string {
    return Environment::PREVIEW;
  }

}
