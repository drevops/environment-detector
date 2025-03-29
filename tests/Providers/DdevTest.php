<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Providers;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Providers\Ddev;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Ddev::class)]
#[CoversClass(Environment::class)]
class DdevTest extends ProviderTestBase {

  public static function dataProviderActive(): array {
    return [
      [fn(): null => NULL, FALSE],
      [fn() => static::envSet('IS_DDEV_PROJECT', 'TRUE'), TRUE],
    ];
  }

  public static function dataProviderData(): array {
    return [
      [
        fn(): null => NULL,
        NULL,
      ],
      [
        fn() => static::envSet('IS_DDEV_PROJECT', 'TRUE'),
        ['IS_DDEV_PROJECT' => 'TRUE'],
      ],
      [
        function (): void {
          static::envSet('IS_DDEV_PROJECT', 'TRUE');
          static::envSet('DDEV_PROJECT', 'project');
          static::envSet('OTHER_VAR', 'other_val');
        },
        [
          'IS_DDEV_PROJECT' => 'TRUE',
          'DDEV_PROJECT' => 'project',
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
        fn() => static::envSet('IS_DDEV_PROJECT', 'TRUE'),
        Environment::LOCAL,
        function ($test): void {
          $test->assertTrue(Environment::isLocal());
        },
      ],
      [
        function (): void {
          static::envSet('IS_DDEV_PROJECT', 'TRUE');
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
