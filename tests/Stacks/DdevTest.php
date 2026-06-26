<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Stacks;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Stacks\Ddev;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Ddev::class)]
#[CoversClass(Environment::class)]
final class DdevTest extends StackTestCase {

  public static function dataProviderActive(): \Iterator|array {
    yield [fn(): null => NULL, FALSE];
    // The DDEV marker is definitive: DDEV always runs in a container, so the
    // marker alone makes it the active stack with no separate container probe.
    yield [fn(): null => self::envSet('IS_DDEV_PROJECT', 'TRUE'), TRUE];
    // An incidental container signal alongside the marker still resolves to
    // DDEV, ahead of the generic container.
    yield [
      function (): void {
          self::envSet('DOCKER', 'TRUE');
          self::envSet('IS_DDEV_PROJECT', 'TRUE');
      }, TRUE,
    ];
  }

  public function testCoexistsWithCiPlatform(): void {
    self::envSet('DOCKER', 'TRUE');
    self::envSet('IS_DDEV_PROJECT', 'TRUE');
    self::envSet('GITHUB_WORKFLOW', 'workflow_name');

    Environment::init(contextualize: FALSE);

    // The CI platform decides the type; DDEV as an inner stack still applies.
    $this->assertSame('github_actions', Environment::getActivePlatform()?->id());
    $this->assertSame(Environment::CI, getenv('ENVIRONMENT_TYPE'));

    $this->assertSame('ddev', Environment::getActiveStack()?->id());
  }

}
