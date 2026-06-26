<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Platforms;

use DrevOps\EnvironmentDetector\Contexts\Drupal;
use DrevOps\EnvironmentDetector\Contexts\DrupalContextualizerInterface;
use DrevOps\EnvironmentDetector\Platforms\AbstractPlatform;
use DrevOps\EnvironmentDetector\Tests\EnvironmentDetectorTestCase;
use DrevOps\EnvironmentDetector\Tests\Fixtures\NotDrupalContext;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractPlatform::class)]
final class AbstractPlatformTest extends EnvironmentDetectorTestCase {

  public function testIdDefaultsToConst(): void {
    $platform = new class() extends AbstractPlatform {

      public function active(): bool {
        return FALSE;
      }

      public function type(): ?string {
        return NULL;
      }

    };

    $this->assertSame('undefined', $platform->id());
  }

  public function testContextualizeViaCapabilityInterface(): void {
    $platform = new class() extends AbstractPlatform implements DrupalContextualizerInterface {

      public function active(): bool {
        return TRUE;
      }

      public function type(): ?string {
        return NULL;
      }

      public function contextualizeDrupal(Drupal $context): void {
        $context->settings['applied'] = 'drupal';
      }

    };

    $settings = [];
    $config = [];
    $context = new Drupal($settings, $config);

    $platform->contextualize($context);

    // The typed capability interface is resolved and called directly.
    $this->assertSame(['applied' => 'drupal'], $settings);
  }

  public function testContextualizeViaRuntimeReflection(): void {
    $platform = new class() extends AbstractPlatform {

      /**
       * Records whether the runtime per-context method ran.
       */
      public bool $contextualized = FALSE;

      public function active(): bool {
        return TRUE;
      }

      public function type(): ?string {
        return NULL;
      }

      public function contextualizeNotDrupalContext(NotDrupalContext $context): void {
        $this->contextualized = TRUE;
      }

    };

    // A context with no capability interface is dispatched by name at runtime.
    $platform->contextualize(new NotDrupalContext());

    $this->assertTrue($platform->contextualized);
  }

  public function testContextualizeWithoutMatchingMethodIsNoop(): void {
    $platform = new class() extends AbstractPlatform {

      public function active(): bool {
        return TRUE;
      }

      public function type(): ?string {
        return NULL;
      }

    };

    $settings = [];
    $config = [];
    $context = new Drupal($settings, $config);

    $platform->contextualize($context);

    // No capability interface and no runtime method, so nothing is applied.
    $this->assertSame([], $settings);
    $this->assertSame([], $config);
  }

}
