<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Providers;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Providers\Tugboat;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Tugboat::class)]
#[CoversClass(Environment::class)]
class TugboatTest extends ProviderTestCase {

  public static function dataProviderActive(): array {
    return [
      [fn(): null => NULL, FALSE],
      [fn() => static::envSet('TUGBOAT_PREVIEW_ID', '65d80b17a79d4412414fa382'), TRUE],
    ];
  }

  public static function dataProviderData(): array {
    return [
      [
        fn(): null => NULL,
        NULL,
      ],
      [
        fn() => static::envSet('TUGBOAT_PREVIEW_ID', '65d80b17a79d4412414fa382'),
        ['TUGBOAT_PREVIEW_ID' => '65d80b17a79d4412414fa382'],
      ],
      [
        function (): void {
          static::envSet('TUGBOAT_PREVIEW_ID', '65d80b17a79d4412414fa382');
          static::envSet('TUGBOAT_PREVIEW', 'pr-123');
          static::envSet('OTHER_VAR', 'other_val');
        },
        [
          'TUGBOAT_PREVIEW_ID' => '65d80b17a79d4412414fa382',
          'TUGBOAT_PREVIEW' => 'pr-123',
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
        fn() => static::envSet('TUGBOAT_PREVIEW_ID', '65d80b17a79d4412414fa382'),
        Environment::PREVIEW,
        function ($test): void {
          $test->assertTrue(Environment::isPreview());
        },
      ],
    ];
  }

}
