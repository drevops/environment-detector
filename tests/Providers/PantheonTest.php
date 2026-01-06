<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Providers;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Providers\Pantheon;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Pantheon::class)]
#[CoversClass(Environment::class)]
final class PantheonTest extends ProviderTestCase {

  public static function dataProviderActive(): \Iterator|array {
    yield [fn(): null => NULL, FALSE];
    yield [fn() => self::envSet('PANTHEON_ENVIRONMENT', 'dev'), TRUE];
    yield [fn() => self::envSet('PANTHEON_ENVIRONMENT', 'test'), TRUE];
    yield [fn() => self::envSet('PANTHEON_ENVIRONMENT', 'lando'), FALSE];
    yield [fn() => self::envSet('PANTHEON_ENVIRONMENT', 'ddev'), FALSE];
  }

  public static function dataProviderData(): \Iterator|array {
    yield [
      fn(): null => NULL,
      NULL,
    ];
    yield [
      fn() => self::envSet('PANTHEON_ENVIRONMENT', 'dev'),
        ['PANTHEON_ENVIRONMENT' => 'dev'],
    ];
    yield [
      function (): void {
          self::envSet('PANTHEON_ENVIRONMENT', 'dev');
          self::envSet('PANTHEON_PROJECT', 'project');
          self::envSet('TERMINUS_PROJECT', 'project');
          self::envSet('OTHER_VAR', 'other_val');
      },
        [
          'PANTHEON_ENVIRONMENT' => 'dev',
          'PANTHEON_PROJECT' => 'project',
          'TERMINUS_PROJECT' => 'project',
        ],
    ];
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
