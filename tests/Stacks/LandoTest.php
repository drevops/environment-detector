<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Stacks;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Stacks\Lando;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Lando::class)]
#[CoversClass(Environment::class)]
final class LandoTest extends StackTestCase {

  public static function dataProviderActive(): \Iterator|array {
    yield [fn(): null => NULL, FALSE];
    // The Lando marker alone is not enough: Lando must be a container first.
    yield [fn(): null => self::envSet('LANDO_INFO', 'TRUE'), FALSE];
    yield [
      function (): void {
          self::envSet('DOCKER', 'TRUE');
          self::envSet('LANDO_INFO', 'TRUE');
      }, TRUE,
    ];
  }

  public function testCoexistsWithCiPlatform(): void {
    self::envSet('DOCKER', 'TRUE');
    self::envSet('LANDO_INFO', 'TRUE');
    self::envSet('CIRCLECI', 'TRUE');

    Environment::init(contextualize: FALSE);

    // Lando inside CI: type is ci, Lando still active as an inner stack.
    $this->assertSame('circleci', Environment::getActivePlatform()?->id());
    $this->assertSame(Environment::CI, getenv('ENVIRONMENT_TYPE'));

    $this->assertSame('lando', Environment::getActiveStack()?->id());
  }

}
