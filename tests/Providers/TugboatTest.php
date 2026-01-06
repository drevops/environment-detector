<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Providers;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Providers\Tugboat;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Tugboat::class)]
#[CoversClass(Environment::class)]
final class TugboatTest extends ProviderTestCase {

  public static function dataProviderActive(): \Iterator|array {
    yield [fn(): null => NULL, FALSE];
    yield [fn() => self::envSet('TUGBOAT_PREVIEW_ID', '65d80b17a79d4412414fa382'), TRUE];
  }

  public static function dataProviderData(): \Iterator|array {
    yield [
      fn(): null => NULL,
      NULL,
    ];
    yield [
      fn() => self::envSet('TUGBOAT_PREVIEW_ID', '65d80b17a79d4412414fa382'),
        ['TUGBOAT_PREVIEW_ID' => '65d80b17a79d4412414fa382'],
    ];
    yield [
      function (): void {
          self::envSet('TUGBOAT_PREVIEW_ID', '65d80b17a79d4412414fa382');
          self::envSet('TUGBOAT_PREVIEW', 'pr-123');
          self::envSet('OTHER_VAR', 'other_val');
      },
        [
          'TUGBOAT_PREVIEW_ID' => '65d80b17a79d4412414fa382',
          'TUGBOAT_PREVIEW' => 'pr-123',
        ],
    ];
  }

  public static function dataProviderType(): \Iterator|array {
    yield [
      fn(): null => NULL,
      NULL,
    ];
    yield [
      fn() => self::envSet('TUGBOAT_PREVIEW_ID', '65d80b17a79d4412414fa382'),
      Environment::PREVIEW,
      function ($test): void {
          $test->assertTrue(Environment::isPreview());
      },
    ];
  }

}
