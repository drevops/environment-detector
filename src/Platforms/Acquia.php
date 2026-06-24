<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Platforms;

use DrevOps\EnvironmentDetector\Contexts\ContextInterface;
use DrevOps\EnvironmentDetector\Contexts\Drupal;
use DrevOps\EnvironmentDetector\Environment;

/**
 * Acquia Cloud hosting platform.
 *
 * @package DrevOps\EnvironmentDetector\Platforms
 */
class Acquia extends AbstractPlatform {

  /**
   * {@inheritdoc}
   */
  public const ID = 'acquia';

  /**
   * {@inheritdoc}
   */
  public function active(): bool {
    return getenv('AH_SITE_ENVIRONMENT') !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function type(): ?string {
    // Any other value is an ephemeral environment with an arbitrary name,
    // such as an on-demand (Continuous Delivery) environment ('ode*').
    return match (getenv('AH_SITE_ENVIRONMENT')) {
      'dev' => Environment::DEVELOPMENT,
      'stage', 'test' => Environment::STAGE,
      'prod' => Environment::PRODUCTION,
      default => Environment::PREVIEW,
    };
  }

  /**
   * {@inheritdoc}
   */
  public function contextualize(ContextInterface $context): void {
    if (!$context instanceof Drupal) {
      return;
    }

    global $settings;
    global $config;

    // Delay the initial database connection.
    $config['acquia_hosting_settings_autoconnect'] = FALSE;

    // Let Drupal create an .htaccess file in writable directories.
    $settings['auto_create_htaccess'] = TRUE;

    $group = getenv('AH_SITE_GROUP');

    // Include the Acquia-provided settings file for the site group. It defines,
    // among other things, the config_vcs_directory read below.
    if (is_string($group) && $group !== '') {
      $file = getenv('DRUPAL_ACQUIA_SETTINGS_FILE') ?: sprintf('/var/www/site-php/%s/%s-settings.inc', $group, $group);
      // @codeCoverageIgnoreStart
      if (is_string($file) && file_exists($file)) {
        require $file;
      }
      // @codeCoverageIgnoreEnd
    }

    // Prefer the explicit config path, then the Acquia-provided VCS directory.
    $config_path = getenv('DRUPAL_CONFIG_PATH');
    if (is_string($config_path) && $config_path !== '') {
      $settings['config_sync_directory'] = $config_path;
    }
    elseif (is_array($settings) && !empty($settings['config_vcs_directory'])) {
      $settings['config_sync_directory'] = $settings['config_vcs_directory'];
    }

    // Temporary files location.
    $settings['file_temp_path'] = '/tmp';
    if (is_string($group) && $group !== '' && getenv('DRUPAL_TMP_PATH_IS_SHARED')) {
      $settings['file_temp_path'] = sprintf('/mnt/gfs/%s.%s/tmp', $group, (string) getenv('AH_SITE_ENVIRONMENT'));
    }
    $tmp_path = getenv('DRUPAL_TMP_PATH');
    if (is_string($tmp_path) && $tmp_path !== '') {
      $settings['file_temp_path'] = $tmp_path;
    }
  }

}
