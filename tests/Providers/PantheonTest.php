<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Providers;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Providers\Pantheon;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Pantheon::class)]
#[CoversClass(Environment::class)]
class PantheonTest extends ProviderTestCase {

  public static function dataProviderActive(): array {
    return [
      [fn(): null => NULL, FALSE],
      [fn() => static::envSet('PANTHEON_ENVIRONMENT', 'dev'), TRUE],
      [fn() => static::envSet('PANTHEON_ENVIRONMENT', 'test'), TRUE],
      [fn() => static::envSet('PANTHEON_ENVIRONMENT', 'lando'), FALSE],
      [fn() => static::envSet('PANTHEON_ENVIRONMENT', 'ddev'), FALSE],
    ];
  }

  public static function dataProviderData(): array {
    return [
      [
        fn(): null => NULL,
        NULL,
      ],
      [
        fn() => static::envSet('PANTHEON_ENVIRONMENT', 'dev'),
        ['PANTHEON_ENVIRONMENT' => 'dev'],
      ],
      [
        function (): void {
          static::envSet('PANTHEON_ENVIRONMENT', 'dev');
          static::envSet('PANTHEON_PROJECT', 'project');
          static::envSet('TERMINUS_PROJECT', 'project');
          static::envSet('OTHER_VAR', 'other_val');
        },
        [
          'PANTHEON_ENVIRONMENT' => 'dev',
          'PANTHEON_PROJECT' => 'project',
          'TERMINUS_PROJECT' => 'project',
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
        fn() => static::envSet('PANTHEON_ENVIRONMENT', 'dev'),
        Environment::DEVELOPMENT,
        function ($test): void {
          $test->assertTrue(Environment::isDev());
        },
      ],
      [
        fn() => static::envSet('PANTHEON_ENVIRONMENT', 'test'),
        Environment::STAGE,
        function (TestCase $test): void {
          $test->assertTrue(Environment::isStage());
        },
      ],

      [
        fn() => static::envSet('PANTHEON_ENVIRONMENT', 'live'),
        Environment::PRODUCTION,
        function ($test): void {
          $test->assertTrue(Environment::isProd());
        },
      ],

      [
        fn() => static::envSet('PANTHEON_ENVIRONMENT', 'lando'),
        NULL,
      ],

      [
        fn() => static::envSet('PANTHEON_ENVIRONMENT', 'ddev'),
        NULL,
      ],

      [
        fn() => static::envSet('PANTHEON_ENVIRONMENT', 'other'),
        Environment::PREVIEW,
        function ($test): void {
          $test->assertTrue(Environment::isPreview());
        },
      ],
    ];
  }

}
