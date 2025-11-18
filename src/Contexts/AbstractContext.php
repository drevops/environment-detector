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
  public const ID = 'undefined';

  /**
   * Context label. Provers should override this constant.
   */
  public const LABEL = 'undefined';

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

  /**
   * {@inheritdoc}
   */
  // phpcs:disable DrupalPractice.Commenting.CommentEmptyLine.SpacingAfter
  // phpcs:disable Drupal.Commenting.FunctionComment.WrongStyle
  // phpcs:disable Squiz.WhiteSpace.FunctionSpacing.After
  // @codeCoverageIgnoreStart
  public function contextualize(): void {
    // Noop.
  }
  // @codeCoverageIgnoreEnd
  // phpcs:enable DrupalPractice.Commenting.CommentEmptyLine.SpacingAfter
  // phpcs:enable Drupal.Commenting.FunctionComment.WrongStyle
  // phpcs:enable Squiz.WhiteSpace.FunctionSpacing.After

}
