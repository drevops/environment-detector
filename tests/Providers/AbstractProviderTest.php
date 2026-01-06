<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Providers;

use DrevOps\EnvironmentDetector\Providers\AbstractProvider;
use DrevOps\EnvironmentDetector\Tests\EnvironmentDetectorTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractProvider::class)]
final class AbstractProviderTest extends EnvironmentDetectorTestCase {

  public function testDataWithEmptyPrefixes(): void {
    $empty_prefixes_provider = $this->mockProvider(
      type: NULL,
      active: TRUE,
      env_prefixes: fn(): array => [],
      id: '',
    );

    $this->assertSame([], $empty_prefixes_provider->data());
  }

}
