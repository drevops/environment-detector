<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Stacks;

use DrevOps\EnvironmentDetector\Stacks\StackInterface;
use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Stacks\Ddev;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Ddev::class)]
#[CoversClass(Environment::class)]
final class DdevTest extends StackTestCase {

  public static function dataProviderActive(): \Iterator|array {
    yield [fn(): null => NULL, FALSE];
    yield [fn(): null => self::envSet('IS_DDEV_PROJECT', 'TRUE'), TRUE];
  }

  public function testCoexistsWithCiPlatform(): void {
    self::envSet('IS_DDEV_PROJECT', 'TRUE');
    self::envSet('GITHUB_WORKFLOW', 'workflow_name');

    Environment::init(contextualize: FALSE);

    // The CI platform decides the type; DDEV as an inner stack still applies.
    $this->assertSame('github_actions', Environment::getActivePlatform()?->id());
    $this->assertSame(Environment::CI, getenv('ENVIRONMENT_TYPE'));

    $active_ids = array_map(static fn(StackInterface $stack): string => $stack->id(), Environment::getActiveStacks());
    $this->assertContains('ddev', $active_ids);
  }

}
