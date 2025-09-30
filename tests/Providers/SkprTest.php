<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Providers;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Providers\Skpr;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Skpr::class)]
#[CoversClass(Environment::class)]
class SkprTest extends ProviderTestCase {

  /**
   * Path to the fixtures directory.
   */
  protected string $fixturesDir;

  /**
   * {@inheritdoc}
   */
  #[\Override]
  protected function setUp(): void {
    parent::setUp();
    $this->fixturesDir = dirname(__DIR__) . '/fixtures/skpr/data';

    if (!defined('DRUPAL_ROOT')) {
      define('DRUPAL_ROOT', '/app/web');
    }
  }

  public static function dataProviderActive(): array {
    return [
      [fn(): null => NULL, FALSE],
      [fn() => static::envSet('SKPR_ENV', 'dev'), TRUE],
      [fn() => static::envSet('OTHER_VAR', 'value'), FALSE],
    ];
  }

  public static function dataProviderData(): array {
    return [
      [
        fn(): null => NULL,
        NULL,
      ],
      [
        fn(): null => static::envSetMultiple([
          'OTHER_VAR' => 'value',
        ]),
        NULL,
      ],
      [
        fn(): null => static::envSetMultiple([
          'SKPR_ENV' => 'dev',
        ]),
        [
          'SKPR_ENV' => 'dev',
        ],
      ],
      [
        fn(): null => static::envSetMultiple([
          'SKPR_ENV' => 'dev',
          'SKPR_PROJECT' => 'myproject',
          'OTHER_VAR' => 'value',
        ]),
        [
          'SKPR_ENV' => 'dev',
          'SKPR_PROJECT' => 'myproject',
        ],
      ],
    ];
  }

  public static function dataProviderType(): array {
    return [
      'no vars' => [
        fn(): null => NULL,
        NULL,
      ],
      'dev env' => [
        fn(): null => static::envSetMultiple([
          'SKPR_ENV' => 'dev',
        ]),
        Environment::DEVELOPMENT,
      ],
      'stg env' => [
        fn(): null => static::envSetMultiple([
          'SKPR_ENV' => 'stg',
        ]),
        Environment::STAGE,
      ],
      'prod env' => [
        fn(): null => static::envSetMultiple([
          'SKPR_ENV' => 'prod',
        ]),
        Environment::PRODUCTION,
      ],
      'unrecognized env' => [
        fn(): null => static::envSetMultiple([
          'SKPR_ENV' => 'custom',
        ]),
        NULL,
      ],
    ];
  }

  #[DataProvider('dataProviderContextualizeDrupal')]
  public function testContextualizeDrupal(callable $before, array $expected, ?callable $after = NULL): void {
    $before();

    static::envSet('SKPR_ENV', 'dev');
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

  public static function dataProviderContextualizeDrupal(): array {
    $default_settings = [
      'environment' => Environment::DEVELOPMENT,
      'hash_salt' => 'abc',
    ];
    $default_config = [];

    return [
      [
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
      ],

      [
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
      ],
    ];
  }

}
