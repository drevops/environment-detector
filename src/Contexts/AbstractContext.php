<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Contexts;

/**
 * Abstract context.
 *
 * All contexts should extend this class.
 *
 * @package DrevOps\EnvironmentDetector\Contexts
 */
abstract class AbstractContext implements ContextInterface {

  /**
   * Context ID. Provers should override this constant.
   */
  public const string ID = 'undefined';

  /**
   * Context label. Provers should override this constant.
   */
  public const string LABEL = 'undefined';

  /**
   * {@inheritdoc}
   */
  public function id(): string {
    return static::ID;
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return static::LABEL;
  }

}
