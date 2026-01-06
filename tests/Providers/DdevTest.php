<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Providers;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Providers\Ddev;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Ddev::class)]
#[CoversClass(Environment::class)]
final class DdevTest extends ProviderTestCase {

  public static function dataProviderActive(): \Iterator|array {
    yield [fn(): null => NULL, FALSE];
    yield [fn() => self::envSet('IS_DDEV_PROJECT', 'TRUE'), TRUE];
  }

  public static function dataProviderData(): \Iterator|array {
    yield [
      fn(): null => NULL,
      NULL,
    ];
    yield [
      fn() => self::envSet('IS_DDEV_PROJECT', 'TRUE'),
        ['IS_DDEV_PROJECT' => 'TRUE'],
    ];
    yield [
      function (): void {
          self::envSet('IS_DDEV_PROJECT', 'TRUE');
          self::envSet('DDEV_PROJECT', 'project');
          self::envSet('OTHER_VAR', 'other_val');
      },
        [
          'IS_DDEV_PROJECT' => 'TRUE',
          'DDEV_PROJECT' => 'project',
        ],
    ];
  }

  public static function dataProviderType(): \Iterator|array {
    yield [
      fn(): null => NULL,
      NULL,
    ];
    yield [
      fn() => self::envSet('IS_DDEV_PROJECT', 'TRUE'),
      Environment::LOCAL,
      function ($test): void {
          $test->assertTrue(Environment::isLocal());
      },
    ];
    yield [
      function (): void {
          self::envSet('IS_DDEV_PROJECT', 'TRUE');
          self::envSet('CI', 'TRUE');
      },
      Environment::CI,
      function ($test): void {
          $test->assertTrue(Environment::isCi());
      },
    ];
  }

}
