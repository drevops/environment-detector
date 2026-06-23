<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Stacks;

use DrevOps\EnvironmentDetector\Stacks\StackInterface;
use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Stacks\Docker;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Docker::class)]
#[CoversClass(Environment::class)]
final class DockerTest extends StackTestCase {

  public static function dataProviderActive(): \Iterator|array {
    yield [fn(): null => NULL, FALSE];
    yield [fn(): null => self::envSet('DOCKER', 'TRUE'), TRUE];
    yield [fn(): null => self::envSet('container', 'TRUE'), TRUE];
    // Docker is a stack now and no longer bows out for DDEV/Lando: with only
    // their markers set (and no Docker markers) Docker simply is not detected.
    yield [fn(): null => self::envSet('IS_DDEV_PROJECT', 'TRUE'), FALSE];
    yield [fn(): null => self::envSet('LANDO_INFO', 'TRUE'), FALSE];
    // Docker markers win regardless of co-existing DDEV/Lando markers.
    yield [
      function (): void {
          self::envSet('DOCKER', 'TRUE');
          self::envSet('IS_DDEV_PROJECT', 'TRUE');
      }, TRUE,
    ];
  }

  public function testCoexistsWithPlatform(): void {
    self::envSet('DOCKER', 'TRUE');
    self::envSet('AH_SITE_ENVIRONMENT', 'prod');

    Environment::init(contextualize: FALSE);

    // The platform decides the type; Docker as an inner stack never collides.
    $this->assertSame('acquia', Environment::getActivePlatform()?->id());
    $this->assertSame(Environment::PRODUCTION, getenv('ENVIRONMENT_TYPE'));

    $active_ids = array_map(static fn(StackInterface $stack): string => $stack->id(), Environment::getActiveStacks());
    $this->assertContains('docker', $active_ids);
  }

  public function testCoexistsWithStack(): void {
    self::envSet('DOCKER', 'TRUE');
    self::envSet('IS_DDEV_PROJECT', 'TRUE');

    Environment::init(contextualize: FALSE);

    $active_ids = array_map(static fn(StackInterface $stack): string => $stack->id(), Environment::getActiveStacks());

    $this->assertContains('docker', $active_ids);
    $this->assertContains('ddev', $active_ids);
  }

}
