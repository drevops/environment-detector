<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Providers;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Providers\PlatformSh;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PlatformSh::class)]
#[CoversClass(Environment::class)]
final class PlatformShTest extends ProviderTestCase {

  public static function dataProviderActive(): \Iterator|array {
    yield [fn(): null => NULL, FALSE];
    yield [fn() => self::envSet('PLATFORM_ENVIRONMENT_TYPE', 'development'), TRUE];
  }

  public static function dataProviderData(): \Iterator|array {
    yield [
      fn(): null => NULL,
      NULL,
    ];
    yield [
      fn() => self::envSet('PLATFORM_ENVIRONMENT_TYPE', 'development'),
        ['PLATFORM_ENVIRONMENT_TYPE' => 'development'],
    ];
    yield [
      function (): void {
          self::envSet('PLATFORM_ENVIRONMENT_TYPE', 'development');
          self::envSet('PLATFORM_PROJECT', 'project');
          self::envSet('OTHER_VAR', 'other_val');
      },
        [
          'PLATFORM_ENVIRONMENT_TYPE' => 'development',
          'PLATFORM_PROJECT' => 'project',
        ],
    ];
  }

  public static function dataProviderType(): \Iterator|array {
    yield [
      fn(): null => NULL,
      NULL,
    ];
    yield [
      fn() => self::envSet('PLATFORM_ENVIRONMENT_TYPE', 'development'),
      Environment::DEVELOPMENT,
      function ($test): void {
          $test->assertTrue(Environment::isDev());
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
  }

}
