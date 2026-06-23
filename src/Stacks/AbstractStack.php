<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Stacks;

use DrevOps\EnvironmentDetector\Contexts\ContextInterface;

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
  // phpcs:disable DrupalPractice.Commenting.CommentEmptyLine.SpacingAfter
  // phpcs:disable Drupal.Commenting.FunctionComment.WrongStyle
  // phpcs:disable Squiz.WhiteSpace.FunctionSpacing.After
  public function contextualize(ContextInterface $context): void {
    // Noop. Override to inject context-specific settings.
  }
  // phpcs:enable DrupalPractice.Commenting.CommentEmptyLine.SpacingAfter
  // phpcs:enable Drupal.Commenting.FunctionComment.WrongStyle
  // phpcs:enable Squiz.WhiteSpace.FunctionSpacing.After

}
