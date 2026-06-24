<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Platforms;

use DrevOps\EnvironmentDetector\Contexts\ContextInterface;

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
