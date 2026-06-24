<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Stacks;

/**
 * Native stack (runs directly on the host, not in a container).
 *
 * @package DrevOps\EnvironmentDetector\Stacks
 */
class Native extends AbstractStack {

  /**
   * {@inheritdoc}
   */
  public const ID = 'native';

  /**
   * {@inheritdoc}
   */
  public function active(): bool {
    return TRUE;
  }

}
