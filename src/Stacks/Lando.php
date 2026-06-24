<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Stacks;

/**
 * Lando stack (a Lando container).
 *
 * @package DrevOps\EnvironmentDetector\Stacks
 */
class Lando extends Container {

  /**
   * {@inheritdoc}
   */
  public const ID = 'lando';

  /**
   * {@inheritdoc}
   */
  public function active(): bool {
    return getenv('LANDO_INFO') !== FALSE;
  }

}
