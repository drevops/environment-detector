<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Platforms;

use DrevOps\EnvironmentDetector\Environment;

/**
 * Tugboat preview-environment platform.
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
    // Tugboat only builds ephemeral per-branch and per-PR environments, so the
    // tier is always preview.
    return Environment::PREVIEW;
  }

}
