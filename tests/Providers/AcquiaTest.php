<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Providers;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Providers\Acquia;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Acquia::class)]
#[CoversClass(Environment::class)]
final class AcquiaTest extends ProviderTestCase {

  public static function dataProviderActive(): \Iterator|array {
    yield [fn(): null => NULL, FALSE];
    yield [fn() => self::envSet('AH_SITE_ENVIRONMENT', 'dev'), TRUE];
  }

  public static function dataProviderData(): \Iterator|array {
    yield [
      fn(): null => NULL,
      NULL,
    ];
    yield [
      fn() => self::envSet('AH_SITE_ENVIRONMENT', 'dev'),
        ['AH_SITE_ENVIRONMENT' => 'dev'],
    ];
    yield [
      function (): void {
          self::envSet('AH_SITE_ENVIRONMENT', 'dev');
          self::envSet('AH_SITE_GROUP', 'group');
          self::envSet('OTHER_VAR', 'other_val');
      },
        [
          'AH_SITE_ENVIRONMENT' => 'dev',
          'AH_SITE_GROUP' => 'group',
        ],
    ];
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
      fn() => self::envSet('AH_SITE_ENVIRONMENT', 'prod'),
      Environment::PRODUCTION,
      function ($test): void {
          $test->assertTrue(Environment::isProd());
      },
    ];
  }

}
