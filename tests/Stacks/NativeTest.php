<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Stacks;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Stacks\Native;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Native::class)]
#[CoversClass(Environment::class)]
final class NativeTest extends StackTestCase {

  public static function dataProviderActive(): \Iterator|array {
    // Bare metal: with no container signal the native host is the active stack.
    yield [fn(): null => NULL, TRUE];
    // Inside a container a container stack wins over the native fallback.
    yield [fn(): null => self::envSet('DOCKER', 'TRUE'), FALSE];
  }

}
