<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Stacks;

/**
 * DDEV stack (a DDEV container).
 *
 * @package DrevOps\EnvironmentDetector\Stacks
 */
class Ddev extends Container {

  /**
   * {@inheritdoc}
   */
  public const ID = 'ddev';

  /**
   * {@inheritdoc}
   */
  public function active(): bool {
    return parent::active() && getenv('IS_DDEV_PROJECT') !== FALSE;
  }

}
