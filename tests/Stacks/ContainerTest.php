<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Stacks;

use DrevOps\EnvironmentDetector\Contexts\Drupal;
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
    // A non-Drupal context hits the type guard and returns without touching it.
    (new Container())->contextualize(new NotDrupalContext());

    $this->expectNotToPerformAssertions();
  }

  #[DataProvider('dataProviderContextualizeDrupal')]
  public function testContextualizeDrupal(callable $before, array $expected): void {
    $before();

    $settings = [];
    $config = [];
    $context = new Drupal($settings, $config);
    (new Container())->contextualize($context);

    $this->assertEquals($expected, $settings);
    $this->assertSame([], $config);
  }

  public static function dataProviderContextualizeDrupal(): \Iterator {
    // The built-in service-host allowlist is always added.
    yield 'allowlist only' => [
      fn(): null => NULL,
      [
        'trusted_host_patterns' => [
          '^(web|app|webserver|nginx|apache|apache2)$',
        ],
      ],
    ];
    // LOCALDEV_URL adds the site's dev host on top of the allowlist.
    yield 'with localdev url' => [
      fn(): null => self::envSet('LOCALDEV_URL', 'https://example-site.docker.amazee.io'),
      [
        'trusted_host_patterns' => [
          '^(web|app|webserver|nginx|apache|apache2)$',
          '^example\-site\.docker\.amazee\.io$',
        ],
      ],
    ];
    // A bare host with no scheme is handled too.
    yield 'bare host localdev url' => [
      fn(): null => self::envSet('LOCALDEV_URL', 'mysite.local'),
      [
        'trusted_host_patterns' => [
          '^(web|app|webserver|nginx|apache|apache2)$',
          '^mysite\.local$',
        ],
      ],
    ];
    // A port and path are stripped down to the host.
    yield 'localdev url with port and path' => [
      fn(): null => self::envSet('LOCALDEV_URL', 'https://example-site.docker.amazee.io:8080/subpath'),
      [
        'trusted_host_patterns' => [
          '^(web|app|webserver|nginx|apache|apache2)$',
          '^example\-site\.docker\.amazee\.io$',
        ],
      ],
    ];
  }

}
