<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector;

use DrevOps\EnvironmentDetector\Contexts\ContextInterface;
use DrevOps\EnvironmentDetector\Contexts\Drupal;
use DrevOps\EnvironmentDetector\Contexts\DrupalContextualizerInterface;

/**
 * Dispatches a context to the matching per-context method on a ring.
 *
 * Shared by platforms and stacks so both apply context-specific settings the
 * same way.
 *
 * @package DrevOps\EnvironmentDetector
 */
trait DispatchesContextualization {

  /**
   * {@inheritdoc}
   */
  public function contextualize(ContextInterface $context): void {
    // The built-in Drupal context is dispatched through the typed interface so
    // the common path never pays for reflection; a ring that does not
    // contextualize Drupal is simply a no-op here.
    if ($context instanceof Drupal) {
      if ($this instanceof DrupalContextualizerInterface) {
        $this->contextualizeDrupal($context);
      }

      return;
    }

    // A custom context falls back to a contextualize<ContextName>() method
    // resolved from its short name, when the ring defines one.
    $method = 'contextualize' . (new \ReflectionClass($context))->getShortName();
    $callable = [$this, $method];

    if (is_callable($callable)) {
      $callable($context);
    }
  }

}
