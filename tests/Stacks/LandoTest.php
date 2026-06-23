<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Stacks;

use DrevOps\EnvironmentDetector\Stacks\StackInterface;
use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Stacks\Lando;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Lando::class)]
#[CoversClass(Environment::class)]
final class LandoTest extends StackTestCase {

  public static function dataProviderActive(): \Iterator|array {
    yield [fn(): null => NULL, FALSE];
    yield [fn(): null => self::envSet('LANDO_INFO', 'TRUE'), TRUE];
  }

  public function testCoexistsWithCiPlatform(): void {
    self::envSet('LANDO_INFO', 'TRUE');
    self::envSet('CIRCLECI', 'TRUE');

    Environment::init(contextualize: FALSE);

    // Lando inside CI: type is ci, Lando still active as an inner stack.
    $this->assertSame('circleci', Environment::getActivePlatform()?->id());
    $this->assertSame(Environment::CI, getenv('ENVIRONMENT_TYPE'));

    $active_ids = array_map(static fn(StackInterface $stack): string => $stack->id(), Environment::getActiveStacks());
    $this->assertContains('lando', $active_ids);
  }

}
