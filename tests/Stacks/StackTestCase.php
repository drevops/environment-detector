<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Stacks;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Tests\EnvironmentDetectorTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

abstract class StackTestCase extends EnvironmentDetectorTestCase {

  /**
   * The stack ID discovered from the test class name.
   */
  protected string $stackId;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Get the stack ID from the test class name.
    $this->stackId = strtolower(str_replace('Test', '', (new \ReflectionClass($this))->getShortName()));
  }

  #[DataProvider('dataProviderActive')]
  public function testActive(callable $before, bool $expect_active, ?callable $after = NULL): void {
    $before();

    $active_id = Environment::getActiveStack()?->id();

    if ($expect_active) {
      $this->assertSame($this->stackId, $active_id, sprintf('Stack %s is active', $this->stackId));
    }
    else {
      $this->assertNotSame($this->stackId, $active_id, sprintf('Stack %s is not active', $this->stackId));
    }

    if ($after !== NULL) {
      $after($this);
    }
  }

  abstract public static function dataProviderActive(): \Iterator|array;

}
