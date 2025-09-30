<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Providers;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Providers\Docker;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Docker::class)]
#[CoversClass(Environment::class)]
class DockerTest extends ProviderTestCase {

  public static function dataProviderActive(): array {
    return [
      [fn(): null => NULL, FALSE],
      [fn(): null => static::envSet('IS_DDEV_PROJECT', 'TRUE'), FALSE],
      [fn(): null => static::envSet('LANDO_INFO', 'TRUE'), FALSE],
      [fn(): null => static::envSet('DOCKER', 'TRUE'), TRUE],
      [fn(): null => static::envSet('container', 'TRUE'), TRUE],
    ];
  }

  public static function dataProviderData(): array {
    return [
      [
        fn(): null => NULL,
        NULL,
      ],
      [
        fn() => static::envSet('DOCKER', 'TRUE'),
        ['DOCKER' => 'TRUE'],
      ],
      [
        function (): void {
          static::envSet('DOCKER', 'TRUE');
          static::envSet('DOCKER_VERSION', '123');
          static::envSet('OTHER_VAR', 'other_val');
        },
        [
          'DOCKER' => 'TRUE',
          'DOCKER_VERSION' => '123',
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
        fn() => static::envSet('DOCKER', 'TRUE'),
        Environment::LOCAL,
        function ($test): void {
          $test->assertTrue(Environment::isLocal());
        },
      ],
      [
        function (): void {
          static::envSet('DOCKER', 'TRUE');
          static::envSet('CI', 'TRUE');
        },
        Environment::CI,
        function ($test): void {
          $test->assertTrue(Environment::isCi());
        },
      ],
    ];
  }

}
