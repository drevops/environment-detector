<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Platforms;

use DrevOps\EnvironmentDetector\Contexts\Drupal;
use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Platforms\Skpr;
use DrevOps\EnvironmentDetector\Tests\Fixtures\NotDrupalContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Skpr::class)]
#[CoversClass(Environment::class)]
final class SkprTest extends PlatformTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    if (!defined('DRUPAL_ROOT')) {
      define('DRUPAL_ROOT', '/app/web');
    }
  }

  public static function dataProviderActive(): \Iterator|array {
    yield [fn(): null => NULL, FALSE];
    yield [fn() => self::envSet('SKPR_ENV', 'dev'), TRUE];
    yield [fn() => self::envSet('OTHER_VAR', 'value'), FALSE];
  }

  public static function dataProviderType(): \Iterator|array {
    yield 'no vars' => [
      fn(): null => NULL,
      NULL,
    ];
    yield 'dev env' => [
      fn(): null => self::envSetMultiple([
        'SKPR_ENV' => 'dev',
      ]),
      Environment::DEVELOPMENT,
    ];
    yield 'stg env' => [
      fn(): null => self::envSetMultiple([
        'SKPR_ENV' => 'stg',
      ]),
      Environment::STAGE,
    ];
    yield 'prod env' => [
      fn(): null => self::envSetMultiple([
        'SKPR_ENV' => 'prod',
      ]),
      Environment::PRODUCTION,
    ];
    yield 'unrecognized env' => [
      fn(): null => self::envSetMultiple([
        'SKPR_ENV' => 'custom',
      ]),
      Environment::PREVIEW,
    ];
  }

  public function testContextualizeNonDrupalIsNoop(): void {
    // A non-Drupal context hits the type guard and returns without touching it.
    (new Skpr())->contextualize(new NotDrupalContext());

    $this->expectNotToPerformAssertions();
  }

  #[DataProvider('dataProviderContextualizeDrupal')]
  public function testContextualizeDrupal(callable $before, array $expected, ?callable $after = NULL): void {
    $before();

    $settings = [];
    $config = [];
    $context = new Drupal($settings, $config);
    (new Skpr())->contextualize($context);

    $this->assertEquals($expected, $settings);

    if ($after !== NULL) {
      $after($this);
    }
  }

  public static function dataProviderContextualizeDrupal(): \Iterator {
    $base = [
      'file_public_path' => 'sites/default/files',
      'file_temp_path' => '/tmp',
      'file_private_path' => 'sites/default/files/private',
      'php_storage' => [
        'twig' => [
          'directory' => '/app/web/../.php',
        ],
      ],
      'trusted_host_patterns' => [
        '^127\.0\.0\.1$',
      ],
    ];
    // Default file paths and the loopback trusted host.
    yield 'defaults' => [
      fn(): null => NULL,
      $base,
    ];
    // A forwarded-for header turns on the reverse-proxy settings.
    yield 'reverse proxy' => [
      fn(): string => $_SERVER['HTTP_X_FORWARDED_FOR'] = '192.168.1.1',
      $base + [
        'reverse_proxy' => TRUE,
        'reverse_proxy_proto_header' => 'HTTP_CLOUDFRONT_FORWARDED_PROTO',
        'reverse_proxy_port_header' => 'SERVER_PORT',
        'reverse_proxy_addresses' => [],
      ],
      function (): void {
          unset($_SERVER['HTTP_X_FORWARDED_FOR']);
      },
    ];
  }

}
