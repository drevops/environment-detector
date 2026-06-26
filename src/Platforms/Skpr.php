<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Platforms;

use DrevOps\EnvironmentDetector\Contexts\Drupal;
use DrevOps\EnvironmentDetector\Contexts\DrupalContextualizerInterface;
use DrevOps\EnvironmentDetector\Environment;
use Skpr\SkprConfig;

/**
 * Skpr hosting platform.
 *
 * @package DrevOps\EnvironmentDetector\Platforms
 */
class Skpr extends AbstractPlatform implements DrupalContextualizerInterface {

  /**
   * {@inheritdoc}
   */
  public const ID = 'skpr';

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
    // Any other value is an environment with an arbitrary name, which is
    // ephemeral by nature - a preview.
    return match (getenv('SKPR_ENV')) {
      'prod' => Environment::PRODUCTION,
      'stg' => Environment::STAGE,
      'dev' => Environment::DEVELOPMENT,
      default => Environment::PREVIEW,
    };
  }

  /**
   * {@inheritdoc}
   *
   * @see https://docs.skpr.io/integrations/drupal
   */
  public function contextualizeDrupal(Drupal $context): void {
    // Outside a real Skpr+Drupal runtime there is nothing to configure. This
    // guard depends on runtime presence of SkprConfig and DRUPAL_ROOT, so it is
    // not exercisable from tests.
    // @codeCoverageIgnoreStart
    if (!class_exists(SkprConfig::class) || !defined('DRUPAL_ROOT')) {
      return;
    }
    // @codeCoverageIgnoreEnd
    $settings = &$context->settings;

    $skpr = SkprConfig::create()->load();

    $settings['file_public_path'] = 'sites/default/files';
    $settings['file_temp_path'] = $skpr->get('mount.temporary') ?: '/tmp';
    $settings['file_private_path'] = $skpr->get('mount.private') ?: 'sites/default/files/private';
    $settings['php_storage']['twig'] = [
      'directory' => ($skpr->get('mount.local') ?: DRUPAL_ROOT . '/..') . '/.php',
    ];

    $settings['trusted_host_patterns'][] = '^127\.0\.0\.1$';
    // Skpr reads hostnames from a fixed absolute path that is only present in a
    // real Skpr runtime, so the loop body cannot be reached from tests.
    foreach ($skpr->hostNames() as $hostname) {
      // @codeCoverageIgnoreStart
      $settings['trusted_host_patterns'][] = '^' . preg_quote($hostname) . '$';
      // @codeCoverageIgnoreEnd
    }

    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $settings['reverse_proxy'] = TRUE;
      $settings['reverse_proxy_proto_header'] = 'HTTP_CLOUDFRONT_FORWARDED_PROTO';
      $settings['reverse_proxy_port_header'] = 'SERVER_PORT';
      $settings['reverse_proxy_addresses'] = $skpr->ipRanges();
    }
  }

}
