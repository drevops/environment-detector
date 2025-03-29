<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Providers;

use DrevOps\EnvironmentDetector\Environment;

/**
 * Lagoon provider.
 *
 * Detects the Lagoon environment type.
 *
 * @package DrevOps\EnvironmentDetector\Providers
 */
class Lagoon extends AbstractProvider {

  /**
   * {@inheritdoc}
   */

  const string ID = 'lagoon';

  /**
   * {@inheritdoc}
   */
  const string LABEL = 'Lagoon';

  /**
   * {@inheritdoc}
   */
  protected static function envPrefixes(): array {
    return ['LAGOON_'];
  }

  /**
   * {@inheritdoc}
   */
  public function active(): bool {
    return getenv('LAGOON_KUBERNETES') !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function type(): ?string {
    $type = NULL;

    // Environment is marked as 'production'.
    if (getenv('LAGOON_ENVIRONMENT_TYPE') == 'production') {
      $type = Environment::PRODUCTION;
    }
    elseif (getenv('LAGOON_ENVIRONMENT_TYPE') == 'development') {
      $type = Environment::DEVELOPMENT;

      // Try to identify production environment using a branch name for
      // the cases when the Lagoon environment is not marked as 'production' yet.
      // Note that `LAGOON_PRODUCTION_BRANCH` is a custom variable that should be
      // set in the Lagoon project settings.
      if (!empty(getenv('LAGOON_GIT_BRANCH')) && !empty(getenv('LAGOON_PRODUCTION_BRANCH')) && getenv('LAGOON_GIT_BRANCH') === getenv('LAGOON_PRODUCTION_BRANCH')) {
        $type = Environment::PRODUCTION;
      }
      // `main` or `master` is a Stage if another branch is used for production.
      elseif (getenv('LAGOON_GIT_BRANCH') == 'main' || getenv('LAGOON_GIT_BRANCH') == 'master') {
        $type = Environment::STAGE;
      }
      // Release and hotfix branches are considered Stage.
      elseif (!empty(getenv('LAGOON_GIT_BRANCH')) && (str_starts_with(getenv('LAGOON_GIT_BRANCH'), 'release/') || str_starts_with(getenv('LAGOON_GIT_BRANCH'), 'hotfix/'))) {
        $type = Environment::STAGE;
      }
    }

    return $type;
  }

}

