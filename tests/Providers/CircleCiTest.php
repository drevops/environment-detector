<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Providers;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Providers\CircleCi;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CircleCi::class)]
#[CoversClass(Environment::class)]
final class CircleCiTest extends ProviderTestCase {

  public static function dataProviderActive(): \Iterator|array {
    yield [fn(): null => NULL, FALSE];
    yield [fn() => self::envSet('CIRCLECI', 'TRUE'), TRUE];
  }

  public static function dataProviderData(): \Iterator|array {
    yield [
      fn(): null => NULL,
      NULL,
    ];
    yield [
      fn() => self::envSet('CIRCLECI', 'TRUE'),
        ['CIRCLECI' => 'TRUE'],
    ];
    yield [
      function (): void {
          self::envSet('CIRCLECI', 'TRUE');
          self::envSet('CIRCLE_BRANCH', 'abc');
          self::envSet('OTHER_VAR', 'other_val');
      },
        [
          'CIRCLECI' => 'TRUE',
          'CIRCLE_BRANCH' => 'abc',
        ],
    ];
  }

  public static function dataProviderType(): \Iterator|array {
    yield [
      fn(): null => NULL,
      NULL,
    ];
    yield [
      fn() => self::envSet('CIRCLECI', 'TRUE'),
      Environment::CI,
      function ($test): void {
          $test->assertTrue(Environment::isCi());
      },
    ];
  }

}
