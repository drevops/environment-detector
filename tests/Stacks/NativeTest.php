<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Stacks;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Stacks\Native;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Native::class)]
#[CoversClass(Environment::class)]
final class NativeTest extends StackTestCase {

  public function testActiveOnBareMetal(): void {
    $this->requireBareMetalHost();

    $this->assertSame('native', Environment::getActiveStack()?->id());
  }

  public static function dataProviderActive(): \Iterator|array {
    // A container stack wins over the native fallback.
    yield [fn(): null => self::envSet('DOCKER', 'TRUE'), FALSE];
  }

}
