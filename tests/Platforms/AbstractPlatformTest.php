<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Platforms;

use DrevOps\EnvironmentDetector\Contexts\Drupal;
use DrevOps\EnvironmentDetector\Platforms\AbstractPlatform;
use DrevOps\EnvironmentDetector\Tests\EnvironmentDetectorTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractPlatform::class)]
final class AbstractPlatformTest extends EnvironmentDetectorTestCase {

  public function testIdDefaultsToConst(): void {
    $platform = new class extends AbstractPlatform {

      public function active(): bool {
        return FALSE;
      }

      public function type(): ?string {
        return NULL;
      }

    };

    $this->assertSame('undefined', $platform->id());
  }

  public function testContextualizeIsNoop(): void {
    $platform = new class extends AbstractPlatform {

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

    // The abstract contextualize is a no-op and leaves the context untouched.
    $this->assertSame([], $settings);
    $this->assertSame([], $config);
  }

}
