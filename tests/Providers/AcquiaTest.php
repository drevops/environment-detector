<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Providers;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Providers\Acquia;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Acquia::class)]
#[CoversClass(Environment::class)]
class AcquiaTest extends ProviderTestBase {

  public static function dataProviderActive(): array {
    return [
      [fn(): null => NULL, FALSE],
      [fn() => static::envSet('AH_SITE_ENVIRONMENT', 'dev'), TRUE],
    ];
  }

  public static function dataProviderData(): array {
    return [
      [
        fn(): null => NULL,
        NULL,
      ],
      [
        fn() => static::envSet('AH_SITE_ENVIRONMENT', 'dev'),
        ['AH_SITE_ENVIRONMENT' => 'dev'],
      ],
      [
        function (): void {
          static::envSet('AH_SITE_ENVIRONMENT', 'dev');
          static::envSet('AH_SITE_GROUP', 'group');
          static::envSet('OTHER_VAR', 'other_val');
        },
        [
          'AH_SITE_ENVIRONMENT' => 'dev',
          'AH_SITE_GROUP' => 'group',
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
        fn() => static::envSet('AH_SITE_ENVIRONMENT', 'dev'),
        Environment::DEVELOPMENT,
        function ($test): void {
          $test->assertTrue(Environment::isDev());
        },
      ],
      [
        fn() => static::envSet('AH_SITE_ENVIRONMENT', 'test'),
        Environment::STAGE,
        function (TestCase $test): void {
          $test->assertTrue(Environment::isStage());
        },
      ],

      [
        fn() => static::envSet('AH_SITE_ENVIRONMENT', 'prod'),
        Environment::PRODUCTION,
        function ($test): void {
          $test->assertTrue(Environment::isProd());
        },
      ],
    ];
  }

}
