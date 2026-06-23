<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Stacks;

/**
 * Lando stack.
 *
 * Detects whether the environment runs inside Lando.
 *
 * @package DrevOps\EnvironmentDetector\Stacks
 */
class Lando extends AbstractStack {

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
