<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Contexts;

use DrevOps\EnvironmentDetector\Contexts\Drupal;
use DrevOps\EnvironmentDetector\Environment;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Drupal::class)]
final class DrupalTest extends ContextTestCase {

  public static function dataProviderActive(): \Iterator {
    yield [
      function (): void {
          global $settings;
          global $config;
          $settings = ['hash_salt' => 'abc'];
          $config = [];
      }, TRUE,
    ];
    yield [
      function (): void {
          global $settings;
          global $config;
          $settings = [];
          $config = ['system.site' => ['uuid' => '123']];
      }, TRUE,
    ];
    yield [
      function (): void {
          global $settings;
          global $config;
          $settings = ['hash_salt' => 'abc'];
          $config = ['system.site' => ['uuid' => '123']];
      }, TRUE,
    ];
  }

  public static function dataProviderContextualize(): \Iterator {
    yield [
      fn(): null => NULL,
      function (ContextTestCase $context_test_case): void {
          global $settings;
          $settings = [];
          $context_test_case->assertArrayNotHasKey('environment', $settings);
      },
    ];
    yield [
      function (): void {
          global $settings;
          $settings = ['hash_salt' => 'abc'];
      },
      function (ContextTestCase $context_test_case): void {
          global $settings;
          $context_test_case->assertEquals(Environment::DEVELOPMENT, $settings['environment']);
      },
    ];
  }

}
