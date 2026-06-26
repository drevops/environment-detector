<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Stacks;

use DrevOps\EnvironmentDetector\Contexts\ContextInterface;
use DrevOps\EnvironmentDetector\Contexts\Drupal;
use DrevOps\EnvironmentDetector\Contexts\DrupalContextualizerInterface;

/**
 * Abstract stack.
 *
 * All stacks should extend this class.
 *
 * @package DrevOps\EnvironmentDetector\Stacks
 */
abstract class AbstractStack implements StackInterface {

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
