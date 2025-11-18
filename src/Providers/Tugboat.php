<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Providers;

use DrevOps\EnvironmentDetector\Environment;

/**
 * Tugboat provider.
 *
 * Detects the Tugboat environment type.
 *
 * @package DrevOps\EnvironmentDetector\Providers
 */
class Tugboat extends AbstractProvider {

  /**
   * {@inheritdoc}
   */
  public const ID = 'tugboat';

  /**
   * {@inheritdoc}
   */
  public const LABEL = 'Tugboat';

  /**
   * {@inheritdoc}
   */
  protected function envPrefixes(): array {
    return ['TUGBOAT_'];
  }

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
