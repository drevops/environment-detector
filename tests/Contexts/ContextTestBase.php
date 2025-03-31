<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Contexts;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Tests\TestBase;
use PHPUnit\Framework\Attributes\DataProvider;

abstract class ContextTestBase extends TestBase {

  /**
   * The context ID discovered from the test class name.
   */
  protected string $contextId;

  /**
   * {@inheritdoc}
   */
  #[\Override]
  protected function setUp(): void {
    parent::setUp();

    // Get the context ID from the test class name.
    $this->contextId = strtolower(str_replace('Test', '', (new \ReflectionClass($this))->getShortName()));
  }

  #[DataProvider('dataProviderActive')]
  public function testActive(callable $before, bool $expect_equals, ?callable $after = NULL): void {
    $before();

    if ($expect_equals) {
      Environment::reset();
      $this->assertEquals($this->contextId, Environment::context()?->id(), sprintf('Context ID is %s', $this->contextId));
      $this->assertNotEmpty(Environment::context()?->label() ?? '', 'Context label is not empty');
    }
    else {
      $this->assertNotEquals($this->contextId, Environment::context()?->id(), sprintf('Context ID is not %s', $this->contextId));
    }

    if ($after !== NULL) {
      $after($this);
    }
  }

  abstract public static function dataProviderActive(): array;

  #[DataProvider('dataProviderContextualize')]
  public function testContextualize(callable $before, ?callable $after = NULL): void {
    $before();

    Environment::reset();
    Environment::context()?->contextualize();

    if ($after !== NULL) {
      $after($this);
    }
  }

  abstract public static function dataProviderContextualize(): array;

}
