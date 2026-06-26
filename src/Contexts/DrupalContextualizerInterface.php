<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Contexts;

/**
 * Drupal contextualizer interface.
 *
 * A platform or stack implements this to apply settings to the Drupal context
 * in a typed way: the detector calls contextualizeDrupal() with the active
 * Drupal context, so an implementing ring needs no manual context type check.
 *
 * @package DrevOps\EnvironmentDetector\Contexts
 */
interface DrupalContextualizerInterface {

  /**
   * Apply settings to the Drupal context.
   *
   * @param \DrevOps\EnvironmentDetector\Contexts\Drupal $context
   *   The Drupal context to apply settings to.
   */
  public function contextualizeDrupal(Drupal $context): void;

}
