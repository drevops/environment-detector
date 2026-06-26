<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Platforms;

use DrevOps\EnvironmentDetector\Contexts\ContextInterface;
use DrevOps\EnvironmentDetector\Contexts\Drupal;
use DrevOps\EnvironmentDetector\Contexts\DrupalContextualizerInterface;

/**
 * Abstract platform.
 *
 * All platforms should extend this class.
 *
 * @package DrevOps\EnvironmentDetector\Platforms
 */
abstract class AbstractPlatform implements PlatformInterface {

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

  /**
   * {@inheritdoc}
   */
  public function contextualize(ContextInterface $context): void {
    // Known interfaces - optimised for speed.
    if ($this instanceof DrupalContextualizerInterface && $context instanceof Drupal) {
      // Only the most-derived contextualizeDrupal() runs, so a subclass that
      // overrides it must call parent::contextualizeDrupal() to keep the
      // parent's settings.
      $this->contextualizeDrupal($context);
      return;
    }

    // Runtime.
    $method = 'contextualize' . (new \ReflectionClass($context))->getShortName();
    $callable = [$this, $method];

    if (is_callable($callable)) {
      $callable($context);
    }
  }

}
