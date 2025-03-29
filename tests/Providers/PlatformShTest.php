<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Providers;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Providers\PlatformSh;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PlatformSh::class)]
#[CoversClass(Environment::class)]
class PlatformShTest extends ProviderTestBase {

  public static function dataProviderActive(): array {
    return [
      [fn(): null => NULL, FALSE],
      [fn() => static::envSet('PLATFORM_ENVIRONMENT_TYPE', 'development'), TRUE],
    ];
  }

  public static function dataProviderData(): array {
    return [
      [
        fn(): null => NULL,
        NULL,
      ],
      [
        fn() => static::envSet('PLATFORM_ENVIRONMENT_TYPE', 'development'),
        ['PLATFORM_ENVIRONMENT_TYPE' => 'development'],
      ],
      [
        function (): void {
          static::envSet('PLATFORM_ENVIRONMENT_TYPE', 'development');
          static::envSet('PLATFORM_PROJECT', 'project');
          static::envSet('OTHER_VAR', 'other_val');
        },
        [
          'PLATFORM_ENVIRONMENT_TYPE' => 'development',
          'PLATFORM_PROJECT' => 'project',
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
        fn() => static::envSet('PLATFORM_ENVIRONMENT_TYPE', 'development'),
        Environment::DEVELOPMENT,
        function ($test): void {
          $test->assertTrue(Environment::isDev());
        },
      ],
      [
        fn() => static::envSet('PLATFORM_ENVIRONMENT_TYPE', 'staging'),
        Environment::STAGE,
        function (TestCase $test): void {
          $test->assertTrue(Environment::isStage());
        },
      ],

      [
        fn() => static::envSet('PLATFORM_ENVIRONMENT_TYPE', 'production'),
        Environment::PRODUCTION,
        function ($test): void {
          $test->assertTrue(Environment::isProd());
        },
      ],
    ];
  }

}
