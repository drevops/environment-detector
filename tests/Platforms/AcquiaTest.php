<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Platforms;

use DrevOps\EnvironmentDetector\Contexts\Drupal;
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
      Environment::PREVIEW,
      function ($test): void {
          $test->assertTrue(Environment::isPreview());
      },
    ];
  }

  public function testContextualizeNonDrupalIsNoop(): void {
    // A non-Drupal context hits the type guard and returns without touching it.
    (new Acquia())->contextualize(new NotDrupalContext());

    $this->expectNotToPerformAssertions();
  }

  #[DataProvider('dataProviderContextualizeDrupal')]
  public function testContextualizeDrupal(callable $before, array $initial_settings, array $expected_settings, array $expected_config): void {
    $before();

    self::envSet('AH_SITE_ENVIRONMENT', 'dev');

    $settings = $initial_settings;
    $config = [];
    $context = new Drupal($settings, $config);
    (new Acquia())->contextualize($context);

    $this->assertEquals($expected_settings, $settings);
    $this->assertEquals($expected_config, $config);
  }

  public static function dataProviderContextualizeDrupal(): \Iterator {
    $config = [
      'acquia_hosting_settings_autoconnect' => FALSE,
    ];
    // Minimal: no group, no config path, no temp overrides.
    yield 'minimal' => [
      fn(): null => NULL,
      [],
      [
        'auto_create_htaccess' => TRUE,
        'file_temp_path' => '/tmp',
      ],
      $config,
    ];
    // Explicit config sync path.
    yield 'explicit config path' => [
      fn(): null => self::envSet('DRUPAL_CONFIG_PATH', '/app/config/sync'),
      [],
      [
        'auto_create_htaccess' => TRUE,
        'config_sync_directory' => '/app/config/sync',
        'file_temp_path' => '/tmp',
      ],
      $config,
    ];
    // Acquia-provided VCS directory fallback.
    yield 'vcs directory fallback' => [
      fn(): null => NULL,
      ['config_vcs_directory' => '/var/www/config'],
      [
        'config_vcs_directory' => '/var/www/config',
        'auto_create_htaccess' => TRUE,
        'config_sync_directory' => '/var/www/config',
        'file_temp_path' => '/tmp',
      ],
      $config,
    ];
    // Explicit temp path override.
    yield 'explicit temp path' => [
      fn(): null => self::envSet('DRUPAL_TMP_PATH', '/custom/tmp'),
      [],
      [
        'auto_create_htaccess' => TRUE,
        'file_temp_path' => '/custom/tmp',
      ],
      $config,
    ];
    // Shared temp path derived from the site group and environment.
    yield 'shared temp path' => [
      function (): void {
          self::envSet('AH_SITE_GROUP', 'mygroup');
          self::envSet('DRUPAL_TMP_PATH_IS_SHARED', '1');
      },
      [],
      [
        'auto_create_htaccess' => TRUE,
        'file_temp_path' => '/mnt/gfs/mygroup.dev/tmp',
      ],
      $config,
    ];
  }

}
