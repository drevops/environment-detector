<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Stacks;

use DrevOps\EnvironmentDetector\Contexts\Drupal;
use DrevOps\EnvironmentDetector\Stacks\AbstractStack;
use DrevOps\EnvironmentDetector\Tests\EnvironmentDetectorTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractStack::class)]
final class AbstractStackTest extends EnvironmentDetectorTestCase {

  public function testIdDefaultsToConst(): void {
    $stack = new class extends AbstractStack {

      public function active(): bool {
        return FALSE;
      }

    };

    $this->assertSame('undefined', $stack->id());
  }

  public function testContextualizeIsNoop(): void {
    $stack = new class extends AbstractStack {

      public function active(): bool {
        return TRUE;
      }

    };

    $settings = [];
    $config = [];
    $context = new Drupal($settings, $config);

    $stack->contextualize($context);

    // The abstract contextualize is a no-op and leaves the context untouched.
    $this->assertSame([], $settings);
    $this->assertSame([], $config);
  }

}
