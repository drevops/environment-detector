<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Platforms;

use DrevOps\EnvironmentDetector\Environment;

/**
 * GitHub Actions continuous integration platform.
 *
 * @package DrevOps\EnvironmentDetector\Platforms
 */
class GitHubActions extends AbstractPlatform {

  /**
   * {@inheritdoc}
   */
  public const ID = 'github_actions';

  /**
   * {@inheritdoc}
   */
  public function active(): bool {
    return getenv('GITHUB_WORKFLOW') !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function type(): ?string {
    return Environment::CI;
  }

}
