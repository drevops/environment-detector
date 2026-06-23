<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Stacks;

/**
 * DDEV stack.
 *
 * Detects whether the environment runs inside DDEV.
 *
 * @package DrevOps\EnvironmentDetector\Stacks
 */
class Ddev extends AbstractStack {

  /**
   * {@inheritdoc}
   */
  public const ID = 'ddev';

  /**
   * {@inheritdoc}
   */
  public function active(): bool {
    return getenv('IS_DDEV_PROJECT') !== FALSE;
  }

}
