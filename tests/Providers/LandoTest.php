<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Providers;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Providers\Lando;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Lando::class)]
#[CoversClass(Environment::class)]
class LandoTest extends ProviderTestBase {

  public static function dataProviderActive(): array {
    return [
      [fn(): null => NULL, FALSE],
      [fn() => static::envSet('LANDO_INFO', 'TRUE'), TRUE],
    ];
  }

  public static function dataProviderData(): array {
    return [
      [
        fn(): null => NULL,
        NULL,
      ],
      [
        fn() => static::envSet('LANDO_INFO', 'TRUE'),
        ['LANDO_INFO' => 'TRUE'],
      ],
      [
        function (): void {
          static::envSet('LANDO_INFO', 'TRUE');
          static::envSet('LANDO_APP_NAME', 'project');
          static::envSet('OTHER_VAR', 'other_val');
        },
        [
          'LANDO_INFO' => 'TRUE',
          'LANDO_APP_NAME' => 'project',
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
        fn() => static::envSet('LANDO_INFO', 'TRUE'),
        Environment::LOCAL,
        function ($test): void {
          $test->assertTrue(Environment::isLocal());
        },
      ],
      [
        function (): void {
          static::envSet('LANDO_INFO', 'TRUE');
          static::envSet('CI', 'TRUE');
        },
        Environment::LOCAL,
        function ($test): void {
          $test->assertTrue(Environment::isLocal());
        },
      ],
    ];
  }

}
