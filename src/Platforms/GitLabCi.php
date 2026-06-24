<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Platforms;

use DrevOps\EnvironmentDetector\Environment;

/**
 * GitLab CI continuous integration platform.
 *
 * @package DrevOps\EnvironmentDetector\Platforms
 */
class GitLabCi extends AbstractPlatform {

  /**
   * {@inheritdoc}
   */
  public const ID = 'gitlab_ci';

  /**
   * {@inheritdoc}
   */
  public function active(): bool {
    return getenv('GITLAB_CI') !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function type(): ?string {
    return Environment::CI;
  }

}
