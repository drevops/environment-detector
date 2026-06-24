<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests;

use DrevOps\EnvironmentDetector\Stacks\StackInterface;
use DrevOps\EnvironmentDetector\Contexts\AbstractContext;
use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Platforms\AbstractPlatform;
use DrevOps\EnvironmentDetector\Stacks\AbstractStack;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Environment::class)]
#[CoversClass(AbstractPlatform::class)]
#[CoversClass(AbstractStack::class)]
#[CoversClass(AbstractContext::class)]
final class EnvironmentTest extends EnvironmentDetectorTestCase {

  public function testConstants(): void {
    $this->assertSame('local', Environment::LOCAL);
    $this->assertSame('ci', Environment::CI);
    $this->assertSame('development', Environment::DEVELOPMENT);
    $this->assertSame('preview', Environment::PREVIEW);
    $this->assertSame('stage', Environment::STAGE);
    $this->assertSame('production', Environment::PRODUCTION);
  }

  public function testPresetEnvironmentTypeWins(): void {
    self::envSet('ENVIRONMENT_TYPE', Environment::PRODUCTION);

    $platform = $this->mockPlatform(Environment::LOCAL, TRUE, id: 'preset-platform');

    Environment::init(contextualize: FALSE, platforms: [$platform]);

    $this->assertSame(Environment::PRODUCTION, getenv('ENVIRONMENT_TYPE'));
  }

  public function testActivePlatformDecidesType(): void {
    $active_platform = $this->mockPlatform(Environment::PRODUCTION, TRUE, id: 'active-platform');
    $inactive_platform = $this->mockPlatform(Environment::LOCAL, FALSE, id: 'inactive-platform');

    Environment::init(platforms: [$active_platform, $inactive_platform]);

    $this->assertSame(Environment::PRODUCTION, getenv('ENVIRONMENT_TYPE'));
  }

  public function testActivePlatformWithNullTypeUsesFallback(): void {
    $null_platform = $this->mockPlatform(NULL, TRUE, id: 'null-platform');

    Environment::init(fallback: Environment::PREVIEW, platforms: [$null_platform]);

    $this->assertSame(Environment::PREVIEW, getenv('ENVIRONMENT_TYPE'));
  }

  public function testNoPlatformWithCiSignalIsCi(): void {
    self::envSet('CI', 'TRUE');

    Environment::init(contextualize: FALSE, platforms: []);

    $this->assertSame(Environment::CI, getenv('ENVIRONMENT_TYPE'));
  }

  public function testNoPlatformWithoutCiSignalIsLocal(): void {
    Environment::init(contextualize: FALSE, platforms: []);

    $this->assertSame(Environment::LOCAL, getenv('ENVIRONMENT_TYPE'));
  }

  #[DataProvider('dataProviderPlatformTypes')]
  public function testPlatformTypes(?string $platform_type, string $fallback, string $expected): void {
    $platform = $this->mockPlatform($platform_type, TRUE, id: 'type-test-platform');

    Environment::init(fallback: $fallback, platforms: [$platform]);

    $this->assertSame($expected, getenv('ENVIRONMENT_TYPE'));
  }

  public static function dataProviderPlatformTypes(): \Iterator {
    yield 'platform returns local' => [Environment::LOCAL, Environment::DEVELOPMENT, Environment::LOCAL];
    yield 'platform returns ci' => [Environment::CI, Environment::DEVELOPMENT, Environment::CI];
    yield 'platform returns development' => [Environment::DEVELOPMENT, Environment::STAGE, Environment::DEVELOPMENT];
    yield 'platform returns preview' => [Environment::PREVIEW, Environment::DEVELOPMENT, Environment::PREVIEW];
    yield 'platform returns stage' => [Environment::STAGE, Environment::DEVELOPMENT, Environment::STAGE];
    yield 'platform returns production' => [Environment::PRODUCTION, Environment::DEVELOPMENT, Environment::PRODUCTION];
    yield 'platform returns null uses fallback' => [NULL, Environment::PREVIEW, Environment::PREVIEW];
  }

  #[DataProvider('dataProviderInitWithParameterCombinations')]
  public function testInitWithParameterCombinations(bool $contextualize, string $fallback, string $expected): void {
    Environment::init(
      contextualize: $contextualize,
      fallback: $fallback,
    );

    $this->assertSame($expected, getenv('ENVIRONMENT_TYPE'));
  }

  public static function dataProviderInitWithParameterCombinations(): \Iterator {
    yield 'default parameters' => [TRUE, Environment::DEVELOPMENT, Environment::LOCAL];
    yield 'contextualize false' => [FALSE, Environment::DEVELOPMENT, Environment::LOCAL];
    yield 'custom fallback ignored without platform' => [TRUE, Environment::PRODUCTION, Environment::LOCAL];
  }

  public function testInitOnlyRunsOnce(): void {
    $platform = $this->mockPlatform(Environment::STAGE, TRUE, id: 'once-platform');

    Environment::init(contextualize: FALSE, platforms: [$platform]);
    $this->assertSame(Environment::STAGE, getenv('ENVIRONMENT_TYPE'));

    // A second init() is a no-op, so the new platform is never consulted.
    Environment::init(contextualize: FALSE, fallback: Environment::PRODUCTION);
    $this->assertSame(Environment::STAGE, getenv('ENVIRONMENT_TYPE'));
  }

  public function testReset(): void {
    $platform = $this->mockPlatform(Environment::STAGE, TRUE, id: 'reset-platform');

    Environment::init(contextualize: FALSE, platforms: [$platform]);
    $this->assertSame(Environment::STAGE, getenv('ENVIRONMENT_TYPE'));

    Environment::reset();
    self::envUnset('ENVIRONMENT_TYPE');

    Environment::init(contextualize: FALSE);
    $this->assertSame(Environment::LOCAL, getenv('ENVIRONMENT_TYPE'));
  }

  public function testResetAll(): void {
    Environment::init(contextualize: FALSE, fallback: Environment::STAGE);
    $this->assertSame(Environment::LOCAL, getenv('ENVIRONMENT_TYPE'));

    Environment::reset(TRUE);
    self::envUnset('ENVIRONMENT_TYPE');

    // After reset(TRUE) the fallback is back to the default; with a platform
    // that cannot resolve a type, the default fallback is used.
    $platform = $this->mockPlatform(NULL, TRUE, id: 'reset-all-platform');
    Environment::init(contextualize: FALSE, platforms: [$platform]);
    $this->assertSame(Environment::DEVELOPMENT, getenv('ENVIRONMENT_TYPE'));
  }

  public function testMultipleActivePlatformsException(): void {
    $platform1 = $this->mockPlatform(Environment::LOCAL, TRUE, id: 'active-id');
    $platform2 = $this->mockPlatform(Environment::STAGE, TRUE, id: 'active-id-2');

    $this->expectException(\Exception::class);
    $this->expectExceptionMessageMatches('/Multiple active environment platforms detected/');

    Environment::init(platforms: [$platform1, $platform2]);
  }

  public function testMultipleActiveContextsException(): void {
    $context1 = $this->mockContext(TRUE, 'active-id');
    $context2 = $this->mockContext(TRUE, 'active-id-2');

    $this->expectException(\Exception::class);
    $this->expectExceptionMessageMatches('/Multiple active contexts detected/');

    Environment::init(contexts: [$context1, $context2]);
  }

  public function testDuplicatePlatformIdsException(): void {
    $platform1 = $this->mockPlatform(Environment::LOCAL, FALSE, id: 'duplicate-id');
    $platform2 = $this->mockPlatform(Environment::STAGE, FALSE, id: 'duplicate-id');

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Platform with ID "duplicate-id" is already registered');

    Environment::init(platforms: [$platform1, $platform2]);
  }

  public function testDuplicateStackIdsException(): void {
    $stack1 = $this->mockStack(FALSE, id: 'duplicate-stack');
    $stack2 = $this->mockStack(FALSE, id: 'duplicate-stack');

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Stack with ID "duplicate-stack" is already registered');

    Environment::init(stacks: [$stack1, $stack2]);
  }

  public function testDuplicateContextIdsException(): void {
    $context1 = $this->mockContext(FALSE, 'duplicate-id');
    $context2 = $this->mockContext(FALSE, 'duplicate-id');

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Context with ID "duplicate-id" is already registered');

    Environment::init(contexts: [$context1, $context2]);
  }

  public function testInitWithInvalidPlatform(): void {
    $invalid_platform = new \stdClass();

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('The platform must implement PlatformInterface');

    // @phpstan-ignore-next-line
    Environment::init(platforms: [$invalid_platform]);
  }

  public function testInitWithInvalidStack(): void {
    $invalid_stack = new \stdClass();

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('The stack must implement StackInterface');

    // @phpstan-ignore-next-line
    Environment::init(stacks: [$invalid_stack]);
  }

  public function testInitWithInvalidContext(): void {
    $invalid_context = new \stdClass();

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('The context must implement ContextInterface');

    // @phpstan-ignore-next-line
    Environment::init(contexts: [$invalid_context]);
  }

  #[DataProvider('dataProviderContextualization')]
  public function testContextualization(bool $contextualize, bool $has_active_context, int $expected_context_calls, int $expected_platform_calls): void {
    $mock_context = $this->mockContext($has_active_context, 'test-context');
    $mock_platform = $this->mockPlatform(Environment::STAGE, TRUE, id: 'test-platform');

    // @phpstan-ignore-next-line
    $mock_context->expects($this->exactly($expected_context_calls))->method('contextualize');
    // @phpstan-ignore-next-line
    $mock_platform->expects($this->exactly($expected_platform_calls))->method('contextualize');

    Environment::init(
      contextualize: $contextualize,
      platforms: [$mock_platform],
      contexts: [$mock_context],
    );

    $this->assertSame(Environment::STAGE, getenv('ENVIRONMENT_TYPE'));
  }

  public static function dataProviderContextualization(): \Iterator {
    yield 'contextualize true with active context' => [TRUE, TRUE, 1, 1];
    yield 'contextualize false with active context' => [FALSE, TRUE, 0, 0];
    yield 'contextualize true without active context' => [TRUE, FALSE, 0, 0];
  }

  public function testActiveStackGetsContextualized(): void {
    $context = $this->mockContext(TRUE, 'stacks-context');
    $platform = $this->mockPlatform(Environment::STAGE, TRUE, id: 'stacks-platform');

    $stack_active = $this->mockStack(TRUE, id: 'stack-active');
    $stack_other = $this->mockStack(TRUE, id: 'stack-other');
    $stack_inactive = $this->mockStack(FALSE, id: 'stack-inactive');

    // Only the single active stack is contextualized; the rest are never
    // consulted once a match is found.
    // @phpstan-ignore-next-line
    $stack_active->expects($this->once())->method('contextualize')->with($context);
    // @phpstan-ignore-next-line
    $stack_other->expects($this->never())->method('contextualize');
    // @phpstan-ignore-next-line
    $stack_inactive->expects($this->never())->method('contextualize');

    Environment::init(
      contextualize: TRUE,
      platforms: [$platform],
      stacks: [$stack_active, $stack_other, $stack_inactive],
      contexts: [$context],
    );

    $this->assertSame(Environment::STAGE, getenv('ENVIRONMENT_TYPE'));
  }

  public function testNoopContextualizeWithRealPlatformAndStack(): void {
    // An active platform and stack that both inherit the no-op contextualize()
    // plus an active Drupal context exercise the abstract no-op path without
    // injecting any platform/stack-specific settings.
    self::envSet('AH_SITE_ENVIRONMENT', 'prod');
    self::envSet('DOCKER', 'TRUE');

    global $settings;
    $settings = ['hash_salt' => 'abc'];

    Environment::init();

    // Re-read the global after init() mutated it via the active context.
    global $settings;

    $this->assertSame(Environment::PRODUCTION, getenv('ENVIRONMENT_TYPE'));
    // Only the generic Drupal context change is applied.
    $this->assertSame(Environment::PRODUCTION, $settings['environment']);
    $this->assertArrayNotHasKey('reverse_proxy', $settings);
  }

  public function testGetActiveStackReturnsContainer(): void {
    self::envSet('DOCKER', 'TRUE');

    $this->assertSame('container', Environment::getActiveStack()?->id());
  }

  public function testGetActiveStackReturnsMostSpecificContainer(): void {
    self::envSet('DOCKER', 'TRUE');
    self::envSet('IS_DDEV_PROJECT', 'TRUE');

    // The generic container is the fallback; a more specific container wins.
    $this->assertSame('ddev', Environment::getActiveStack()?->id());
  }

  public function testGetActiveStackReturnsNullOnBareMetal(): void {
    $this->assertNotInstanceOf(StackInterface::class, Environment::getActiveStack());
  }

  public function testActivePlatformWithContainerStack(): void {
    self::envSet('DOCKER', 'TRUE');
    self::envSet('AH_SITE_ENVIRONMENT', 'prod');

    Environment::init(contextualize: FALSE);

    $this->assertSame('acquia', Environment::getActivePlatform()?->id());
    $this->assertSame(Environment::PRODUCTION, getenv('ENVIRONMENT_TYPE'));
    $this->assertSame('container', Environment::getActiveStack()?->id());
  }

  #[DataProvider('dataProviderIsEnvironmentTypeMethods')]
  public function testIsEnvironmentTypeMethods(string $env_type, array $expected_results): void {
    self::envSet('ENVIRONMENT_TYPE', $env_type);

    $this->assertEquals($expected_results['isLocal'], Environment::isLocal());
    $this->assertEquals($expected_results['isCi'], Environment::isCi());
    $this->assertEquals($expected_results['isDev'], Environment::isDev());
    $this->assertEquals($expected_results['isPreview'], Environment::isPreview());
    $this->assertEquals($expected_results['isStage'], Environment::isStage());
    $this->assertEquals($expected_results['isProd'], Environment::isProd());
  }

  public static function dataProviderIsEnvironmentTypeMethods(): \Iterator {
    yield 'local environment' => [
      Environment::LOCAL,
        [
          'isLocal' => TRUE,
          'isCi' => FALSE,
          'isDev' => FALSE,
          'isPreview' => FALSE,
          'isStage' => FALSE,
          'isProd' => FALSE,
        ],
    ];
    yield 'ci environment' => [
      Environment::CI,
        [
          'isLocal' => FALSE,
          'isCi' => TRUE,
          'isDev' => FALSE,
          'isPreview' => FALSE,
          'isStage' => FALSE,
          'isProd' => FALSE,
        ],
    ];
    yield 'development environment' => [
      Environment::DEVELOPMENT,
        [
          'isLocal' => FALSE,
          'isCi' => FALSE,
          'isDev' => TRUE,
          'isPreview' => FALSE,
          'isStage' => FALSE,
          'isProd' => FALSE,
        ],
    ];
    yield 'preview environment' => [
      Environment::PREVIEW,
        [
          'isLocal' => FALSE,
          'isCi' => FALSE,
          'isDev' => FALSE,
          'isPreview' => TRUE,
          'isStage' => FALSE,
          'isProd' => FALSE,
        ],
    ];
    yield 'stage environment' => [
      Environment::STAGE,
        [
          'isLocal' => FALSE,
          'isCi' => FALSE,
          'isDev' => FALSE,
          'isPreview' => FALSE,
          'isStage' => TRUE,
          'isProd' => FALSE,
        ],
    ];
    yield 'production environment' => [
      Environment::PRODUCTION,
        [
          'isLocal' => FALSE,
          'isCi' => FALSE,
          'isDev' => FALSE,
          'isPreview' => FALSE,
          'isStage' => FALSE,
          'isProd' => TRUE,
        ],
    ];
  }

  #[DataProvider('dataProviderIsMethodWithCustomTypes')]
  public function testIsMethodWithCustomTypes(string $env_type, string $test_type, bool $expected): void {
    self::envSet('ENVIRONMENT_TYPE', $env_type);
    $this->assertSame($expected, Environment::is($test_type));
  }

  public static function dataProviderIsMethodWithCustomTypes(): \Iterator {
    yield 'custom type matches' => ['custom-env', 'custom-env', TRUE];
    yield 'custom type does not match' => ['custom-env', 'different-env', FALSE];
    yield 'standard type matches custom' => [Environment::LOCAL, Environment::LOCAL, TRUE];
    yield 'standard type does not match custom' => [Environment::LOCAL, 'custom-env', FALSE];
  }

}
