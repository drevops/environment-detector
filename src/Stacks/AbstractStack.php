<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Stacks;

use DrevOps\EnvironmentDetector\DispatchesContextualization;

/**
 * Abstract stack.
 *
 * All stacks should extend this class.
 *
 * @package DrevOps\EnvironmentDetector\Stacks
 */
abstract class AbstractStack implements StackInterface {

  use DispatchesContextualization;

  /**
   * Stack ID. Stacks should override this constant.
   */
  public const ID = 'undefined';

  /**
   * {@inheritdoc}
   */
  public function id(): string {
    return static::ID;
  }

}
