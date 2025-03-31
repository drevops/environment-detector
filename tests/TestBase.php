<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests;

use DrevOps\EnvironmentDetector\Contexts\ContextInterface;
use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Providers\AbstractProvider;
use DrevOps\EnvironmentDetector\Providers\ProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Environment::class)]
class TestBase extends TestCase {

  use EnvTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Unset all environment variables that might be set by the environment
    // where these tests are running.
    static::envUnsetPrefix('GITHUB_');
    static::envUnsetPrefix('DOCKER_');
    static::envUnsetPrefix('RUNNER_');
    static::envUnsetPrefix('CI');
    static::envUnset('ENVIRONMENT_TYPE');

    Environment::reset();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    static::envReset();
    Environment::reset();
  }

  protected function mockProvider(string|callable|null $type = Environment::DEVELOPMENT, bool|callable|null $active = TRUE, callable|null $contextualize = NULL, ?callable $env_prefixes = NULL, string $id = 'mocked_provider'): ProviderInterface {
    $mock = $this->createPartialMock(AbstractProvider::class, [
      'type',
      'active',
      'contextualize',
      'envPrefixes',
      'id',
    ]);

    if (is_callable($type)) {
      $mock->method('type')->willReturnCallback($type);
    }
    else {
      $mock->method('type')->willReturn($type);
    }

    if (is_callable($active)) {
      $mock->method('active')->willReturnCallback($active);
    }
    else {
      $mock->method('active')->willReturn($active);
    }

    if (is_callable($contextualize)) {
      $mock->method('contextualize')->willReturnCallback($contextualize);
    }

    if (is_callable($env_prefixes)) {
      $mock->method('envPrefixes')->willReturnCallback($env_prefixes);
    }

    $mock->method('id')->willReturn($id);

    return $mock;
  }

  protected function mockContext(bool|callable|null $active = TRUE, string $id = 'mocked_context', ?callable $contextualize = NULL): ContextInterface {
    $mock = $this->createMock(ContextInterface::class);

    if (is_callable($active)) {
      $mock->method('active')->willReturnCallback($active);
    }
    else {
      $mock->method('active')->willReturn($active);
    }

    if (is_callable($contextualize)) {
      $mock->method('contextualize')->willReturnCallback($contextualize);
    }

    $mock->method('id')->willReturn($id);

    return $mock;
  }

}
