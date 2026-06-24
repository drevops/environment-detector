<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Fixtures;

use DrevOps\EnvironmentDetector\Contexts\ContextInterface;

/**
 * A minimal non-Drupal context used to exercise platform/stack guards.
 */
class NotDrupalContext implements ContextInterface {

  /**
   * {@inheritdoc}
   */
  public function id(): string {
    return 'not_drupal';
  }

  /**
   * {@inheritdoc}
   */
  public function active(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  // phpcs:disable DrupalPractice.Commenting.CommentEmptyLine.SpacingAfter
  // phpcs:disable Drupal.Commenting.FunctionComment.WrongStyle
  // phpcs:disable Squiz.WhiteSpace.FunctionSpacing.After
  public function contextualize(): void {
    // Noop.
  }
  // phpcs:enable DrupalPractice.Commenting.CommentEmptyLine.SpacingAfter
  // phpcs:enable Drupal.Commenting.FunctionComment.WrongStyle
  // phpcs:enable Squiz.WhiteSpace.FunctionSpacing.After

}
