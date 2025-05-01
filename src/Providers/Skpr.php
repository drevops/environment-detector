<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Providers;

use DrevOps\EnvironmentDetector\Environment;
use Skpr\SkprConfig;

/**
 * Skpr provider.
 *
 * Detects the Skpr environment type.
 *
 * @package DrevOps\EnvironmentDetector\Providers
 */
class Skpr extends AbstractProvider {

  /**
   * {@inheritdoc}
   */
  const string ID = 'skpr';

  /**
   * {@inheritdoc}
   */
  const string LABEL = 'Skpr';

  /**
   * {@inheritdoc}
   */
  protected function envPrefixes(): array {
    return ['SKPR_'];
  }

  /**
   * {@inheritdoc}
   */
  public function active(): bool {
    return getenv('SKPR_ENV') !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function type(): ?string {
    $type = getenv('SKPR_ENV');

    return match ($type) {
      'prod' => Environment::PRODUCTION,
      'stg' => Environment::STAGE,
      'dev' => Environment::DEVELOPMENT,
      default => NULL,
    };
  }

  /**
   * Applies Drupal context.
   *
   * @see https://docs.skpr.io/integrations/drupal
   */
  public static function contextualizeDrupal(): void {
    if (!class_exists(SkprConfig::class) || !defined('DRUPAL_ROOT')) {
      return;
    }

    global $settings;

    $skpr = SkprConfig::create()->load();

    $settings['file_public_path'] = 'sites/default/files';
    $settings['file_temp_path'] = $skpr->get('mount.temporary') ?: '/tmp';
    $settings['file_private_path'] = $skpr->get('mount.private') ?: 'sites/default/files/private';
    $settings['php_storage']['twig'] = [
      'directory' => ($skpr->get('mount.local') ?: DRUPAL_ROOT . '/..') . '/.php',
    ];

    $settings['trusted_host_patterns'][] = '^127\.0\.0\.1$';
    foreach ($skpr->hostNames() as $hostname) {
      $settings['trusted_host_patterns'][] = '^' . preg_quote($hostname) . '$';
    }

    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $settings['reverse_proxy'] = TRUE;
      $settings['reverse_proxy_proto_header'] = 'HTTP_CLOUDFRONT_FORWARDED_PROTO';
      $settings['reverse_proxy_port_header'] = 'SERVER_PORT';
      $settings['reverse_proxy_addresses'] = $skpr->ipRanges();
    }
  }

}
