<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Contexts;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Tests\EnvTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

abstract class ContextTestBase extends TestCase {

  use EnvTrait;

  /**
   * The context ID discovered from the test class name.
   */
  protected string $contextId;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Get the context ID from the test class name.
    $this->contextId = strtolower(str_replace('Test', '', (new \ReflectionClass($this))->getShortName()));
    Environment::reset();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    parent::tearDown();
    static::envReset();
    Environment::reset();
  }

  #[DataProvider('dataProviderActive')]
  public function testActive(callable $before, bool $expect_equals, ?callable $after = NULL): void {
    $data = $before();

    if ($expect_equals) {
      Environment::reset();
      $this->assertEquals($this->contextId, Environment::context($data)?->id());
      $this->assertNotEmpty(Environment::context()?->label() ?? '');
    }
    else {
      $this->assertNotEquals($this->contextId, Environment::context()?->id());
    }

    if ($after !== NULL) {
      $after($this);
    }
  }

  abstract public static function dataProviderActive(): array;

}
