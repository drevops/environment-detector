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
  public const string ID = 'tugboat';

  /**
   * {@inheritdoc}
   */
  public const string LABEL = 'Tugboat';

  /**
   * {@inheritdoc}
   */
  protected static function envPrefixes(): array {
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
