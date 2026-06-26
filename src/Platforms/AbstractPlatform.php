<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Platforms;

use DrevOps\EnvironmentDetector\DispatchesContextualization;

/**
 * Abstract platform.
 *
 * All platforms should extend this class.
 *
 * @package DrevOps\EnvironmentDetector\Platforms
 */
abstract class AbstractPlatform implements PlatformInterface {

  use DispatchesContextualization;

  /**
   * Platform ID. Platforms should override this constant.
   */
  public const ID = 'undefined';

  /**
   * The Git branch name that designates the development environment.
   */
  protected const DEVELOPMENT_BRANCH = 'develop';

  /**
   * {@inheritdoc}
   */
  public function id(): string {
    return static::ID;
  }

}
