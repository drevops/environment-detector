<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Platforms;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Platforms\PlatformSh;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PlatformSh::class)]
#[CoversClass(Environment::class)]
final class PlatformShTest extends PlatformTestCase {

  public static function dataProviderActive(): \Iterator|array {
    yield [fn(): null => NULL, FALSE];
    yield [fn() => self::envSet('PLATFORM_ENVIRONMENT_TYPE', 'development'), TRUE];
  }

  public static function dataProviderType(): \Iterator|array {
    yield [
      fn(): null => NULL,
      NULL,
    ];
    yield [
      fn() => self::envSetMultiple([
        'PLATFORM_ENVIRONMENT_TYPE' => 'development',
        'PLATFORM_BRANCH' => 'develop',
      ]),
      Environment::DEVELOPMENT,
      function ($test): void {
          $test->assertTrue(Environment::isDev());
      },
    ];
    yield [
      fn() => self::envSetMultiple([
        'PLATFORM_ENVIRONMENT_TYPE' => 'development',
        'PLATFORM_BRANCH' => 'feature/foo',
      ]),
      Environment::PREVIEW,
      function ($test): void {
          $test->assertTrue(Environment::isPreview());
      },
    ];
    yield [
      fn() => self::envSet('PLATFORM_ENVIRONMENT_TYPE', 'development'),
      Environment::PREVIEW,
      function ($test): void {
          $test->assertTrue(Environment::isPreview());
      },
    ];
    yield [
      fn() => self::envSet('PLATFORM_ENVIRONMENT_TYPE', 'staging'),
      Environment::STAGE,
      function (TestCase $test_case): void {
          $test_case->assertTrue(Environment::isStage());
      },
    ];
    yield [
      fn() => self::envSet('PLATFORM_ENVIRONMENT_TYPE', 'production'),
      Environment::PRODUCTION,
      function ($test): void {
          $test->assertTrue(Environment::isProd());
      },
    ];
    yield [
      fn() => self::envSet('PLATFORM_ENVIRONMENT_TYPE', 'qa'),
      NULL,
    ];
  }

}
