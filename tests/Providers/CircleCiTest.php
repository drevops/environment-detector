<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Providers;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Providers\CircleCi;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CircleCi::class)]
#[CoversClass(Environment::class)]
class CircleCiTest extends ProviderTestCase {

  public static function dataProviderActive(): array {
    return [
      [fn(): null => NULL, FALSE],
      [fn() => static::envSet('CIRCLECI', 'TRUE'), TRUE],
    ];
  }

  public static function dataProviderData(): array {
    return [
      [
        fn(): null => NULL,
        NULL,
      ],
      [
        fn() => static::envSet('CIRCLECI', 'TRUE'),
        ['CIRCLECI' => 'TRUE'],
      ],
      [
        function (): void {
          static::envSet('CIRCLECI', 'TRUE');
          static::envSet('CIRCLE_BRANCH', 'abc');
          static::envSet('OTHER_VAR', 'other_val');
        },
        [
          'CIRCLECI' => 'TRUE',
          'CIRCLE_BRANCH' => 'abc',
        ],
      ],
    ];
  }

  public static function dataProviderType(): array {
    return [
      [
        fn(): null => NULL,
        NULL,
      ],
      [
        fn() => static::envSet('CIRCLECI', 'TRUE'),
        Environment::CI,
        function ($test): void {
          $test->assertTrue(Environment::isCi());
        },
      ],
    ];
  }

}
