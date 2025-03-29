<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Providers;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Tests\EnvTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

abstract class ProviderTestBase extends TestCase {

  use EnvTrait;

  /**
   * The provider ID discovered from the test class name.
   */
  protected string $providerId;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Get the provider ID from the test class name.
    $this->providerId = strtolower(str_replace('Test', '', (new \ReflectionClass($this))->getShortName()));
    // Unset all environment variables that might be set by the environment
    // where these tests are running.
    static::envUnsetPrefix('GITHUB_');
    static::envUnsetPrefix('DOCKER_');
    static::envUnsetPrefix('RUNNER_');
    static::envUnsetPrefix('CI');
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
    $before();

    if ($expect_equals) {
      $this->assertEquals($this->providerId, Environment::provider()?->id());
      $this->assertNotEmpty(Environment::provider()?->label() ?? '');
    }
    else {
      $this->assertNotEquals($this->providerId, Environment::provider()?->id());
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
