<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Platforms;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Platforms\Tugboat;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Tugboat::class)]
#[CoversClass(Environment::class)]
final class TugboatTest extends PlatformTestCase {

  public static function dataProviderActive(): \Iterator|array {
    yield [fn(): null => NULL, FALSE];
    yield [fn() => self::envSet('TUGBOAT_PREVIEW_ID', '65d80b17a79d4412414fa382'), TRUE];
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
