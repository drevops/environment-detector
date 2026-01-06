<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Providers;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Providers\Skpr;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Skpr::class)]
#[CoversClass(Environment::class)]
final class SkprTest extends ProviderTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    if (!defined('DRUPAL_ROOT')) {
      define('DRUPAL_ROOT', '/app/web');
    }
  }

  public static function dataProviderActive(): \Iterator|array {
    yield [fn(): null => NULL, FALSE];
    yield [fn() => self::envSet('SKPR_ENV', 'dev'), TRUE];
    yield [fn() => self::envSet('OTHER_VAR', 'value'), FALSE];
  }

  public static function dataProviderData(): \Iterator|array {
    yield [
      fn(): null => NULL,
      NULL,
    ];
    yield [
      fn(): null => self::envSetMultiple([
        'OTHER_VAR' => 'value',
      ]),
      NULL,
    ];
    yield [
      fn(): null => self::envSetMultiple([
        'SKPR_ENV' => 'dev',
      ]),
        [
          'SKPR_ENV' => 'dev',
        ],
    ];
    yield [
      fn(): null => self::envSetMultiple([
        'SKPR_ENV' => 'dev',
        'SKPR_PROJECT' => 'myproject',
        'OTHER_VAR' => 'value',
      ]),
        [
          'SKPR_ENV' => 'dev',
          'SKPR_PROJECT' => 'myproject',
        ],
    ];
  }

  public static function dataProviderType(): \Iterator|array {
    yield 'no vars' => [
      fn(): null => NULL,
      NULL,
    ];
    yield 'dev env' => [
      fn(): null => self::envSetMultiple([
        'SKPR_ENV' => 'dev',
      ]),
      Environment::DEVELOPMENT,
    ];
    yield 'stg env' => [
      fn(): null => self::envSetMultiple([
        'SKPR_ENV' => 'stg',
      ]),
      Environment::STAGE,
    ];
    yield 'prod env' => [
      fn(): null => self::envSetMultiple([
        'SKPR_ENV' => 'prod',
      ]),
      Environment::PRODUCTION,
    ];
    yield 'unrecognized env' => [
      fn(): null => self::envSetMultiple([
        'SKPR_ENV' => 'custom',
      ]),
      NULL,
    ];
  }

  #[DataProvider('dataProviderContextualizeDrupal')]
  public function testContextualizeDrupal(callable $before, array $expected, ?callable $after = NULL): void {
    $before();

    self::envSet('SKPR_ENV', 'dev');
    Environment::init();

    global $settings;
    global $config;

    $this->assertEquals($expected['settings'], $settings);

    if (isset($expected['config'])) {
      $this->assertEquals($expected['config'], $config);
    }

    if ($after !== NULL) {
      $after($this);
    }
  }

  public static function dataProviderContextualizeDrupal(): \Iterator {
    $default_settings = [
      'environment' => Environment::DEVELOPMENT,
      'hash_salt' => 'abc',
    ];
    $default_config = [];
    yield [
      function () use ($default_settings, $default_config): void {
        global $settings;
        global $config;
        $settings = $default_settings;
        $config = $default_config;
      },
      [
        'settings' => array_merge_recursive(
          [
            'file_public_path' => 'sites/default/files',
            'file_temp_path' => '/tmp',
            'file_private_path' => 'sites/default/files/private',
            'php_storage' => [
              'twig' => [
                'directory' => '/app/web/../.php',
              ],
            ],
            'trusted_host_patterns' => [
              '^127\.0\.0\.1$',
            ],
          ],
          $default_settings
        ),
        'config' => array_merge_recursive([], $default_config),
      ],
    ];
    yield [
      function () use ($default_settings, $default_config): void {
        global $settings;
        global $config;
        $settings = $default_settings;
        $config = $default_config;
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '192.168.1.1';
      },
      [
        'settings' => array_merge_recursive(
          [
            'file_public_path' => 'sites/default/files',
            'file_temp_path' => '/tmp',
            'file_private_path' => 'sites/default/files/private',
            'php_storage' => [
              'twig' => [
                'directory' => '/app/web/../.php',
              ],
            ],
            'trusted_host_patterns' => [
              '^127\.0\.0\.1$',
            ],
            'reverse_proxy' => TRUE,
            'reverse_proxy_proto_header' => 'HTTP_CLOUDFRONT_FORWARDED_PROTO',
            'reverse_proxy_port_header' => 'SERVER_PORT',
            'reverse_proxy_addresses' => [],
          ],
          $default_settings
        ),
        'config' => array_merge_recursive([], $default_config),
      ],
      function (): void {
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
      },
    ];
  }

}
