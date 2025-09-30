<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Contexts;

use DrevOps\EnvironmentDetector\Contexts\Drupal;
use DrevOps\EnvironmentDetector\Environment;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Drupal::class)]
class DrupalTest extends ContextTestCase {

  public static function dataProviderActive(): array {
    return [
      [
        function (): void {
          global $settings;
          global $config;
          $settings = ['hash_salt' => 'abc'];
          $config = [];
        }, TRUE,
      ],
      [
        function (): void {
          global $settings;
          global $config;
          $settings = [];
          $config = ['system.site' => ['uuid' => '123']];
        }, TRUE,
      ],
      [
        function (): void {
          global $settings;
          global $config;
          $settings = ['hash_salt' => 'abc'];
          $config = ['system.site' => ['uuid' => '123']];
        }, TRUE,
      ],
    ];
  }

  public static function dataProviderContextualize(): array {
    return [
      [
        fn(): null => NULL,
        function (ContextTestCase $test): void {
          global $settings;
          $settings = [];
          $test->assertArrayNotHasKey('environment', $settings);
        },
      ],
      [
        function (): void {
          global $settings;
          $settings = ['hash_salt' => 'abc'];
        },
        function (ContextTestCase $test): void {
          global $settings;
          $test->assertEquals(Environment::DEVELOPMENT, $settings['environment']);
        },
      ],
    ];
  }

}
