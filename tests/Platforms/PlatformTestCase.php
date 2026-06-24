<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Platforms;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Tests\EnvironmentDetectorTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

abstract class PlatformTestCase extends EnvironmentDetectorTestCase {

  /**
   * The platform ID discovered from the test class name.
   */
  protected string $platformId;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Get the platform ID from the test class name.
    $this->platformId = strtolower(str_replace('Test', '', (new \ReflectionClass($this))->getShortName()));
  }

  #[DataProvider('dataProviderActive')]
  public function testActive(callable $before, bool $expect_equals, ?callable $after = NULL): void {
    $before();

    if ($expect_equals) {
      $this->assertSame($this->platformId, Environment::getActivePlatform()?->id(), sprintf('Platform ID is %s', $this->platformId));
    }
    else {
      $this->assertNotSame($this->platformId, Environment::getActivePlatform()?->id(), sprintf('Platform ID is not %s', $this->platformId));
    }

    if ($after !== NULL) {
      $after($this);
    }
  }

  abstract public static function dataProviderActive(): \Iterator|array;

  #[DataProvider('dataProviderType')]
  public function testType(callable $before, ?string $expected, ?callable $after = NULL): void {
    $before();

    $this->assertEquals($expected, Environment::getActivePlatform()?->type());

    if ($after !== NULL) {
      $after($this);
    }
  }

  abstract public static function dataProviderType(): \Iterator|array;

}
