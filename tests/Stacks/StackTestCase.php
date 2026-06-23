<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Stacks;

use DrevOps\EnvironmentDetector\Stacks\StackInterface;
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

    $active_ids = array_map(static fn(StackInterface $stack): string => $stack->id(), Environment::getActiveStacks());

    if ($expect_active) {
      $this->assertContains($this->stackId, $active_ids, sprintf('Stack %s is active', $this->stackId));
    }
    else {
      $this->assertNotContains($this->stackId, $active_ids, sprintf('Stack %s is not active', $this->stackId));
    }

    if ($after !== NULL) {
      $after($this);
    }
  }

  abstract public static function dataProviderActive(): \Iterator|array;

}
