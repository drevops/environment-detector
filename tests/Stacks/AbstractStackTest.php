<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Stacks;

use DrevOps\EnvironmentDetector\Contexts\Drupal;
use DrevOps\EnvironmentDetector\Contexts\DrupalContextualizerInterface;
use DrevOps\EnvironmentDetector\Stacks\AbstractStack;
use DrevOps\EnvironmentDetector\Tests\EnvironmentDetectorTestCase;
use DrevOps\EnvironmentDetector\Tests\Fixtures\NotDrupalContext;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractStack::class)]
final class AbstractStackTest extends EnvironmentDetectorTestCase {

  public function testIdDefaultsToConst(): void {
    $stack = new class() extends AbstractStack {

      public function active(): bool {
        return FALSE;
      }

    };

    $this->assertSame('undefined', $stack->id());
  }

  public function testContextualizeViaCapabilityInterface(): void {
    $stack = new class() extends AbstractStack implements DrupalContextualizerInterface {

      public function active(): bool {
        return TRUE;
      }

      public function contextualizeDrupal(Drupal $context): void {
        $context->settings['applied'] = 'drupal';
      }

    };

    $settings = [];
    $config = [];
    $context = new Drupal($settings, $config);

    $stack->contextualize($context);

    // The typed capability interface is resolved and called directly.
    $this->assertSame(['applied' => 'drupal'], $settings);
  }

  public function testContextualizeViaRuntimeReflection(): void {
    $stack = new class() extends AbstractStack {

      /**
       * Records whether the runtime per-context method ran.
       */
      public bool $contextualized = FALSE;

      public function active(): bool {
        return TRUE;
      }

      public function contextualizeNotDrupalContext(NotDrupalContext $context): void {
        $this->contextualized = TRUE;
      }

    };

    // A context with no capability interface is dispatched by name at runtime.
    $stack->contextualize(new NotDrupalContext());

    $this->assertTrue($stack->contextualized);
  }

  public function testContextualizeWithoutMatchingMethodIsNoop(): void {
    $stack = new class() extends AbstractStack {

      public function active(): bool {
        return TRUE;
      }

    };

    $settings = [];
    $config = [];
    $context = new Drupal($settings, $config);

    $stack->contextualize($context);

    // No capability interface and no runtime method, so nothing is applied.
    $this->assertSame([], $settings);
    $this->assertSame([], $config);
  }

}
