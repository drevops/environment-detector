<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Providers;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Providers\Lando;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Lando::class)]
#[CoversClass(Environment::class)]
final class LandoTest extends ProviderTestCase {

  public static function dataProviderActive(): \Iterator|array {
    yield [fn(): null => NULL, FALSE];
    yield [fn() => self::envSet('LANDO_INFO', 'TRUE'), TRUE];
  }

  public static function dataProviderData(): \Iterator|array {
    yield [
      fn(): null => NULL,
      NULL,
    ];
    yield [
      fn() => self::envSet('LANDO_INFO', 'TRUE'),
        ['LANDO_INFO' => 'TRUE'],
    ];
    yield [
      function (): void {
          self::envSet('LANDO_INFO', 'TRUE');
          self::envSet('LANDO_APP_NAME', 'project');
          self::envSet('OTHER_VAR', 'other_val');
      },
        [
          'LANDO_INFO' => 'TRUE',
          'LANDO_APP_NAME' => 'project',
        ],
    ];
  }

  public static function dataProviderType(): \Iterator|array {
    yield [
      fn(): null => NULL,
      NULL,
    ];
    yield [
      fn() => self::envSet('LANDO_INFO', 'TRUE'),
      Environment::LOCAL,
      function ($test): void {
          $test->assertTrue(Environment::isLocal());
      },
    ];
    yield [
      function (): void {
          self::envSet('LANDO_INFO', 'TRUE');
          self::envSet('CI', 'TRUE');
      },
      Environment::LOCAL,
      function ($test): void {
          $test->assertTrue(Environment::isLocal());
      },
    ];
  }

}
