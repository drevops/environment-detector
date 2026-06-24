<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests;

use AlexSkrypnyk\PhpunitHelpers\Traits\EnvTrait;
use DrevOps\EnvironmentDetector\Contexts\ContextInterface;
use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Platforms\PlatformInterface;
use DrevOps\EnvironmentDetector\Stacks\StackInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Environment::class)]
class EnvironmentDetectorTestCase extends TestCase {

  use EnvTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    static::envUnset('ENVIRONMENT_TYPE');

    // Isolate detection from any platform or stack markers the host running
    // these tests might already expose, so detection sees only what each test
    // sets for itself.
    static::envUnset('container');
    static::envUnset('IS_DDEV_PROJECT');
    static::envUnset('LANDO_INFO');
    static::envUnsetPrefix('AH_');
    static::envUnsetPrefix('LAGOON_');
    static::envUnsetPrefix('ENVIRONMENT_');
    static::envUnsetPrefix('PANTHEON_');
    static::envUnsetPrefix('TERMINUS_');
    static::envUnsetPrefix('PLATFORM_');
    static::envUnsetPrefix('SKPR_');
    static::envUnsetPrefix('TUGBOAT_');
    static::envUnsetPrefix('CIRCLE');
    static::envUnsetPrefix('GITLAB_');
    static::envUnsetPrefix('GITHUB_');
    static::envUnsetPrefix('DDEV_');
    static::envUnsetPrefix('DOCKER');
    static::envUnsetPrefix('RUNNER_');
    static::envUnsetPrefix('CI');

    global $settings;
    $settings = [];
    global $config;
    $config = [];
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    static::envReset();
    Environment::reset(TRUE);
  }

  /**
   * Skip a test whose expectation only holds on a bare-metal host.
   *
   * The container stacks probe the real filesystem (/.dockerenv, cgroups),
   * which environment isolation cannot mask. A suite run inside a container
   * would therefore see a container stack take precedence over the native
   * fallback, so cases asserting the native (bare-metal) outcome are skipped.
   */
  protected function requireBareMetalHost(): void {
    $active_stack_id = Environment::getActiveStack()?->id();

    // Probing cached the resolved stack; clear it so a later init() re-registers
    // its own stacks from scratch instead of returning this stale result.
    Environment::reset();

    if ($active_stack_id !== 'native') {
      $this->markTestSkipped('Test requires a bare-metal host where the native stack is active.');
    }
  }

  protected function mockPlatform(string|callable|null $type = Environment::DEVELOPMENT, bool|callable|null $active = TRUE, callable|null $contextualize = NULL, string $id = 'mocked_platform'): PlatformInterface {
    $mock = $this->createMock(PlatformInterface::class);

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

    if (!empty($id)) {
      $mock->expects($this->atLeastOnce())->method('id')->willReturn($id);
    }
    else {
      $mock->expects($this->never())->method('id');
    }

    return $mock;
  }

  protected function mockStack(bool|callable|null $active = TRUE, callable|null $contextualize = NULL, string $id = 'mocked_stack'): StackInterface {
    $mock = $this->createMock(StackInterface::class);

    if (is_callable($active)) {
      $mock->method('active')->willReturnCallback($active);
    }
    else {
      $mock->method('active')->willReturn($active);
    }

    if (is_callable($contextualize)) {
      $mock->method('contextualize')->willReturnCallback($contextualize);
    }

    if (!empty($id)) {
      $mock->expects($this->atLeastOnce())->method('id')->willReturn($id);
    }
    else {
      $mock->expects($this->never())->method('id');
    }

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

    $mock->expects($this->atLeastOnce())->method('id')->willReturn($id);

    return $mock;
  }

}
