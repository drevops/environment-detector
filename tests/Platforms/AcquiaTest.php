<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Platforms;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Platforms\Acquia;
use PHPUnit\Framework\Attributes\CoversClass;
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

}
