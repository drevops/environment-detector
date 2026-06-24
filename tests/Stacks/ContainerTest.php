<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Stacks;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Stacks\Container;
use DrevOps\EnvironmentDetector\Tests\Fixtures\NotDrupalContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Container::class)]
#[CoversClass(Environment::class)]
final class ContainerTest extends StackTestCase {

  public static function dataProviderActive(): \Iterator|array {
    yield [fn(): null => NULL, FALSE];
    yield [fn(): null => self::envSet('DOCKER', 'TRUE'), TRUE];
    yield [fn(): null => self::envSet('container', 'TRUE'), TRUE];
    // A specific container marker alone, with no actual container signal, does
    // not make the generic container active.
    yield [fn(): null => self::envSet('IS_DDEV_PROJECT', 'TRUE'), FALSE];
    yield [fn(): null => self::envSet('LANDO_INFO', 'TRUE'), FALSE];
    // A more specific container takes precedence over the generic one.
    yield [
      function (): void {
          self::envSet('DOCKER', 'TRUE');
          self::envSet('IS_DDEV_PROJECT', 'TRUE');
      }, FALSE,
    ];
  }

  public function testCoexistsWithPlatform(): void {
    self::envSet('DOCKER', 'TRUE');
    self::envSet('AH_SITE_ENVIRONMENT', 'prod');

    Environment::init(contextualize: FALSE);

    // The platform decides the type; the container as an inner stack never
    // collides.
    $this->assertSame('acquia', Environment::getActivePlatform()?->id());
    $this->assertSame(Environment::PRODUCTION, getenv('ENVIRONMENT_TYPE'));

    $this->assertSame('container', Environment::getActiveStack()?->id());
  }

  public function testMoreSpecificContainerWins(): void {
    self::envSet('DOCKER', 'TRUE');
    self::envSet('IS_DDEV_PROJECT', 'TRUE');

    Environment::init(contextualize: FALSE);

    // A more specific container is detected ahead of the generic one.
    $this->assertSame('ddev', Environment::getActiveStack()?->id());
  }

  public function testContextualizeNonDrupalIsNoop(): void {
    self::envSet('DOCKER', 'TRUE');
    self::envSet('DRUPAL_DEV_TRUSTED_HOSTS', 'https://example.local,webserver');

    global $settings;
    $settings = [];

    // A non-Drupal context must not trigger any settings injection.
    (new Container())->contextualize(new NotDrupalContext());

    $this->assertSame([], $settings);
  }

  #[DataProvider('dataProviderContextualizeDrupal')]
  public function testContextualizeDrupal(callable $before, array $expected): void {
    $before();

    self::envSet('DOCKER', 'TRUE');
    Environment::init();

    global $settings;

    $this->assertEquals($expected, $settings);
  }

  public static function dataProviderContextualizeDrupal(): \Iterator {
    // No trusted host list - nothing added beyond the resolved environment.
    yield [
      function (): void {
          global $settings;
          $settings = ['hash_salt' => 'abc'];
      },
        [
          'hash_salt' => 'abc',
          'environment' => Environment::LOCAL,
        ],
    ];
    // A comma-separated URL/host list becomes one trusted-host regex.
    yield [
      function (): void {
          global $settings;
          $settings = ['hash_salt' => 'abc'];
          self::envSet('DRUPAL_DEV_TRUSTED_HOSTS', 'https://mysite.local,webserver');
      },
        [
          'hash_salt' => 'abc',
          'environment' => Environment::LOCAL,
          'trusted_host_patterns' => [
            '^(mysite\.local|webserver)$',
          ],
        ],
    ];
  }

}
