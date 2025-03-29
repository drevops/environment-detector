<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Providers;

use DrevOps\EnvironmentDetector\Environment;

/**
 * GitLabCi provider.
 *
 * Detects the GitLabCi environment type.
 *
 * @package DrevOps\EnvironmentDetector\Providers
 */
class GitLabCi extends AbstractProvider {

  /**
   * {@inheritdoc}
   */
  public const string ID = 'gitlab_ci';

  /**
   * {@inheritdoc}
   */
  public const string LABEL = 'GitLab CI';

  /**
   * {@inheritdoc}
   */
  protected static function envPrefixes(): array {
    return ['GITLAB_', 'CI_'];
  }

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
