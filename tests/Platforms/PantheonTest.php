<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Platforms;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Platforms\Pantheon;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Pantheon::class)]
#[CoversClass(Environment::class)]
final class PantheonTest extends PlatformTestCase {

  public static function dataProviderActive(): \Iterator|array {
    yield [fn(): null => NULL, FALSE];
    yield [fn() => self::envSet('PANTHEON_ENVIRONMENT', 'dev'), TRUE];
    yield [fn() => self::envSet('PANTHEON_ENVIRONMENT', 'test'), TRUE];
    yield [fn() => self::envSet('PANTHEON_ENVIRONMENT', 'lando'), FALSE];
    yield [fn() => self::envSet('PANTHEON_ENVIRONMENT', 'ddev'), FALSE];
  }

  public static function dataProviderType(): \Iterator|array {
    yield [
      fn(): null => NULL,
      NULL,
    ];
    yield [
      fn() => self::envSet('PANTHEON_ENVIRONMENT', 'dev'),
      Environment::DEVELOPMENT,
      function ($test): void {
          $test->assertTrue(Environment::isDev());
      },
    ];
    yield [
      fn() => self::envSet('PANTHEON_ENVIRONMENT', 'test'),
      Environment::STAGE,
      function (TestCase $test_case): void {
          $test_case->assertTrue(Environment::isStage());
      },
    ];
    yield [
      fn() => self::envSet('PANTHEON_ENVIRONMENT', 'live'),
      Environment::PRODUCTION,
      function ($test): void {
          $test->assertTrue(Environment::isProd());
      },
    ];
    yield [
      fn() => self::envSet('PANTHEON_ENVIRONMENT', 'lando'),
      NULL,
    ];
    yield [
      fn() => self::envSet('PANTHEON_ENVIRONMENT', 'ddev'),
      NULL,
    ];
    yield [
      fn() => self::envSet('PANTHEON_ENVIRONMENT', 'other'),
      Environment::PREVIEW,
      function ($test): void {
          $test->assertTrue(Environment::isPreview());
      },
    ];
  }

}
