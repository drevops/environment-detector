<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Providers;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Providers\Docker;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Docker::class)]
#[CoversClass(Environment::class)]
final class DockerTest extends ProviderTestCase {

  public static function dataProviderActive(): \Iterator|array {
    yield [fn(): null => NULL, FALSE];
    yield [fn(): null => self::envSet('IS_DDEV_PROJECT', 'TRUE'), FALSE];
    yield [fn(): null => self::envSet('LANDO_INFO', 'TRUE'), FALSE];
    yield [fn(): null => self::envSet('DOCKER', 'TRUE'), TRUE];
    yield [fn(): null => self::envSet('container', 'TRUE'), TRUE];
  }

  public static function dataProviderData(): \Iterator|array {
    yield [
      fn(): null => NULL,
      NULL,
    ];
    yield [
      fn() => self::envSet('DOCKER', 'TRUE'),
        ['DOCKER' => 'TRUE'],
    ];
    yield [
      function (): void {
          self::envSet('DOCKER', 'TRUE');
          self::envSet('DOCKER_VERSION', '123');
          self::envSet('OTHER_VAR', 'other_val');
      },
        [
          'DOCKER' => 'TRUE',
          'DOCKER_VERSION' => '123',
        ],
    ];
  }

  public static function dataProviderType(): \Iterator|array {
    yield [
      fn(): null => NULL,
      NULL,
    ];
    yield [
      fn() => self::envSet('DOCKER', 'TRUE'),
      Environment::LOCAL,
      function ($test): void {
          $test->assertTrue(Environment::isLocal());
      },
    ];
    yield [
      function (): void {
          self::envSet('DOCKER', 'TRUE');
          self::envSet('CI', 'TRUE');
      },
      Environment::CI,
      function ($test): void {
          $test->assertTrue(Environment::isCi());
      },
    ];
  }

}
