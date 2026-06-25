<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Contexts;

use DrevOps\EnvironmentDetector\Contexts\Drupal;
use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Tests\EnvironmentDetectorTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Drupal::class)]
final class DrupalTest extends EnvironmentDetectorTestCase {

  #[DataProvider('dataProviderActive')]
  public function testActive(array $settings, array $config, bool $expected): void {
    $context = new Drupal($settings, $config);

    $this->assertSame($expected, $context->active());
  }

  public static function dataProviderActive(): \Iterator {
    yield 'empty' => [[], [], FALSE];
    yield 'empty hash_salt' => [['hash_salt' => ''], [], FALSE];
    yield 'hash_salt' => [['hash_salt' => 'abc'], [], TRUE];
    yield 'site uuid' => [[], ['system.site' => ['uuid' => '123']], TRUE];
    yield 'both' => [['hash_salt' => 'abc'], ['system.site' => ['uuid' => '123']], TRUE];
  }

  public function testContextualize(): void {
    self::envSet('ENVIRONMENT_TYPE', Environment::LOCAL);

    $settings = ['hash_salt' => 'abc'];
    $config = [];
    $context = new Drupal($settings, $config);
    $context->contextualize();

    // The context writes the type and the universal loopback set.
    $this->assertSame([
      'hash_salt' => 'abc',
      'environment' => Environment::LOCAL,
      'trusted_host_patterns' => ['^localhost$', '^127\.0\.0\.1$'],
    ], $settings);
  }

  public function testReferenceBindingMutatesCaller(): void {
    $settings = [];
    $config = [];
    $context = new Drupal($settings, $config);

    $context->settings['injected'] = 'value';
    $context->config['system.site'] = ['uuid' => '123'];

    // The constructor binds the caller's variables by reference, so writes
    // through the context land back in them.
    $this->assertSame(['injected' => 'value'], $settings);
    $this->assertSame(['system.site' => ['uuid' => '123']], $config);
  }

}
