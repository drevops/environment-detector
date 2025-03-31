<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Providers;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Tests\TestBase;
use PHPUnit\Framework\Attributes\DataProvider;

abstract class ProviderTestBase extends TestBase {

  /**
   * The provider ID discovered from the test class name.
   */
  protected string $providerId;

  /**
   * {@inheritdoc}
   */
  #[\Override]
  protected function setUp(): void {
    parent::setUp();

    // Get the provider ID from the test class name.
    $this->providerId = strtolower(str_replace('Test', '', (new \ReflectionClass($this))->getShortName()));
  }

  #[DataProvider('dataProviderActive')]
  public function testActive(callable $before, bool $expect_equals, ?callable $after = NULL): void {
    $before();

    if ($expect_equals) {
      $this->assertEquals($this->providerId, Environment::provider()?->id(), sprintf('Provider ID is %s', $this->providerId));
      $this->assertNotEmpty(Environment::provider()?->label() ?? '', 'Provider label is not empty');
    }
    else {
      $this->assertNotEquals($this->providerId, Environment::provider()?->id(), sprintf('Provider ID is not %s', $this->providerId));
    }

    if ($after !== NULL) {
      $after($this);
    }
  }

  abstract public static function dataProviderActive(): array;

  #[DataProvider('dataProviderData')]
  public function testData(callable $before, ?array $expected, ?callable $after = NULL): void {
    $before();

    $this->assertEquals($expected, Environment::provider()?->data());

    if ($after !== NULL) {
      $after($this);
    }
  }

  abstract public static function dataProviderData(): array;

  #[DataProvider('dataProviderType')]
  public function testType(callable $before, ?string $expected, ?callable $after = NULL): void {
    $before();

    $this->assertEquals($expected, Environment::provider()?->type());

    if ($after !== NULL) {
      $after($this);
    }
  }

  abstract public static function dataProviderType(): array;

}
