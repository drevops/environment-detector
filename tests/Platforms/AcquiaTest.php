<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Platforms;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Platforms\Acquia;
use DrevOps\EnvironmentDetector\Tests\Fixtures\NotDrupalContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Acquia::class)]
#[CoversClass(Environment::class)]
final class AcquiaTest extends PlatformTestCase {

  public static function dataProviderActive(): \Iterator|array {
    yield [fn(): null => NULL, FALSE];
    yield [fn() => self::envSet('AH_SITE_ENVIRONMENT', 'dev'), TRUE];
  }

  public static function dataProviderType(): \Iterator|array {
    yield [
      fn(): null => NULL,
      NULL,
    ];
    yield [
      fn() => self::envSet('AH_SITE_ENVIRONMENT', 'dev'),
      Environment::DEVELOPMENT,
      function ($test): void {
          $test->assertTrue(Environment::isDev());
      },
    ];
    yield [
      fn() => self::envSet('AH_SITE_ENVIRONMENT', 'test'),
      Environment::STAGE,
      function (TestCase $test_case): void {
          $test_case->assertTrue(Environment::isStage());
      },
    ];
    yield [
      fn() => self::envSet('AH_SITE_ENVIRONMENT', 'stage'),
      Environment::STAGE,
      function ($test): void {
          $test->assertTrue(Environment::isStage());
      },
    ];
    yield [
      fn() => self::envSet('AH_SITE_ENVIRONMENT', 'prod'),
      Environment::PRODUCTION,
      function ($test): void {
          $test->assertTrue(Environment::isProd());
      },
    ];
    yield [
      fn() => self::envSet('AH_SITE_ENVIRONMENT', 'ode123'),
      Environment::PREVIEW,
      function ($test): void {
          $test->assertTrue(Environment::isPreview());
      },
    ];
    yield [
      fn() => self::envSet('AH_SITE_ENVIRONMENT', 'ode'),
      Environment::PREVIEW,
      function ($test): void {
          $test->assertTrue(Environment::isPreview());
      },
    ];
    yield [
      fn() => self::envSet('AH_SITE_ENVIRONMENT', 'something'),
      NULL,
      function ($test): void {
          $test->assertTrue(Environment::isDev());
      },
    ];
  }

  public function testContextualizeNonDrupalIsNoop(): void {
    self::envSet('AH_SITE_ENVIRONMENT', 'prod');

    global $settings;
    global $config;
    $settings = [];
    $config = [];

    // A non-Drupal context must not trigger any settings injection.
    (new Acquia())->contextualize(new NotDrupalContext());

    $this->assertSame([], $settings);
    $this->assertSame([], $config);
  }

  #[DataProvider('dataProviderContextualizeDrupal')]
  public function testContextualizeDrupal(callable $before, array $expected): void {
    $before();

    self::envSet('AH_SITE_ENVIRONMENT', 'dev');
    Environment::init();

    global $settings;
    global $config;

    $this->assertEquals($expected['settings'], $settings);
    $this->assertEquals($expected['config'], $config);
  }

  public static function dataProviderContextualizeDrupal(): \Iterator {
    $config = [
      'acquia_hosting_settings_autoconnect' => FALSE,
    ];
    // Minimal: no group, no config path, no temp overrides.
    yield [
      function (): void {
        global $settings;
        global $config;
        $settings = ['hash_salt' => 'abc'];
        $config = [];
      },
      [
        'settings' => [
          'hash_salt' => 'abc',
          'environment' => Environment::DEVELOPMENT,
          'auto_create_htaccess' => TRUE,
          'file_temp_path' => '/tmp',
        ],
        'config' => $config,
      ],
    ];
    // Explicit config sync path.
    yield [
      function (): void {
        global $settings;
        global $config;
        $settings = ['hash_salt' => 'abc'];
        $config = [];
        self::envSet('DRUPAL_CONFIG_PATH', '/app/config/sync');
      },
      [
        'settings' => [
          'hash_salt' => 'abc',
          'environment' => Environment::DEVELOPMENT,
          'auto_create_htaccess' => TRUE,
          'config_sync_directory' => '/app/config/sync',
          'file_temp_path' => '/tmp',
        ],
        'config' => $config,
      ],
    ];
    // Acquia-provided VCS directory fallback.
    yield [
      function (): void {
        global $settings;
        global $config;
        $settings = ['hash_salt' => 'abc', 'config_vcs_directory' => '/var/www/config'];
        $config = [];
      },
      [
        'settings' => [
          'hash_salt' => 'abc',
          'config_vcs_directory' => '/var/www/config',
          'environment' => Environment::DEVELOPMENT,
          'auto_create_htaccess' => TRUE,
          'config_sync_directory' => '/var/www/config',
          'file_temp_path' => '/tmp',
        ],
        'config' => $config,
      ],
    ];
    // Explicit temp path override.
    yield [
      function (): void {
        global $settings;
        global $config;
        $settings = ['hash_salt' => 'abc'];
        $config = [];
        self::envSet('DRUPAL_TMP_PATH', '/custom/tmp');
      },
      [
        'settings' => [
          'hash_salt' => 'abc',
          'environment' => Environment::DEVELOPMENT,
          'auto_create_htaccess' => TRUE,
          'file_temp_path' => '/custom/tmp',
        ],
        'config' => $config,
      ],
    ];
    // Shared temp path derived from the site group and environment.
    yield [
      function (): void {
        global $settings;
        global $config;
        $settings = ['hash_salt' => 'abc'];
        $config = [];
        self::envSet('AH_SITE_GROUP', 'mygroup');
        self::envSet('DRUPAL_TMP_PATH_IS_SHARED', '1');
      },
      [
        'settings' => [
          'hash_salt' => 'abc',
          'environment' => Environment::DEVELOPMENT,
          'auto_create_htaccess' => TRUE,
          'file_temp_path' => '/mnt/gfs/mygroup.dev/tmp',
        ],
        'config' => $config,
      ],
    ];
  }

}
