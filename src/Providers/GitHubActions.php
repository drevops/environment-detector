<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Providers;

use DrevOps\EnvironmentDetector\Environment;

/**
 * GitHubActions provider.
 *
 * Detects the GitHubActions environment type.
 *
 * @package DrevOps\EnvironmentDetector\Providers
 */
class GitHubActions extends AbstractProvider {

  /**
   * {@inheritdoc}
   */
  public const string ID = 'github_actions';

  /**
   * {@inheritdoc}
   */
  public const string LABEL = 'GitHub Actions';

  /**
   * {@inheritdoc}
   */
  protected function envPrefixes(): array {
    return ['GITHUB_', 'CI', 'RUNNER_'];
  }

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
