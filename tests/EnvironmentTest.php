<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests;

use DrevOps\EnvironmentDetector\Contexts\AbstractContext;
use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Providers\AbstractProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Environment::class)]
#[CoversClass(AbstractProvider::class)]
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

  #[DataProvider('dataProviderEnvironmentTypeDetection')]
  public function testEnvironmentTypeDetection(?string $env_var, string $expected, array $providers, ?callable $override, string $fallback): void {
    if ($env_var !== NULL) {
      self::envSet('ENVIRONMENT_TYPE', $env_var);
    }

    Environment::init(
      contextualize: FALSE,
      fallback: $fallback,
      override: $override,
      providers: $providers
    );

    $this->assertSame($expected, getenv('ENVIRONMENT_TYPE'));
  }

  public static function dataProviderEnvironmentTypeDetection(): \Iterator {
    yield 'pre-set env var' => [
      Environment::PRODUCTION,
      Environment::PRODUCTION,
        [],
      NULL,
      Environment::DEVELOPMENT,
    ];
    yield 'fallback when no providers' => [
      NULL,
      Environment::PREVIEW,
        [],
      NULL,
      Environment::PREVIEW,
    ];
    yield 'override callback changes type' => [
      NULL,
      Environment::CI,
        [],
      fn($provider, $type): string => Environment::CI,
      Environment::DEVELOPMENT,
    ];
    yield 'override callback returning null uses fallback' => [
      NULL,
      Environment::STAGE,
        [],
      fn($provider, $type): null => NULL,
      Environment::STAGE,
    ];
  }

  #[DataProvider('dataProviderInitWithParameterCombinations')]
  public function testInitWithParameterCombinations(bool $contextualize, string $fallback, ?callable $override, array $providers, array $contexts, string $expected): void {
    Environment::init(
      contextualize: $contextualize,
      fallback: $fallback,
      override: $override,
      providers: $providers,
      contexts: $contexts
    );

    $this->assertSame($expected, getenv('ENVIRONMENT_TYPE'));
  }

  public static function dataProviderInitWithParameterCombinations(): \Iterator {
    yield 'default parameters' => [
      TRUE,
      Environment::DEVELOPMENT,
      NULL,
        [],
        [],
      Environment::DEVELOPMENT,
    ];
    yield 'contextualize false' => [
      FALSE,
      Environment::DEVELOPMENT,
      NULL,
        [],
        [],
      Environment::DEVELOPMENT,
    ];
    yield 'custom fallback' => [
      TRUE,
      Environment::PRODUCTION,
      NULL,
        [],
        [],
      Environment::PRODUCTION,
    ];
  }

  public function testInitOnlyRunsOnce(): void {
    Environment::init(fallback: Environment::STAGE);
    $this->assertSame(Environment::STAGE, getenv('ENVIRONMENT_TYPE'));

    Environment::init(fallback: Environment::PRODUCTION);
    $this->assertSame(Environment::STAGE, getenv('ENVIRONMENT_TYPE'));
  }

  public function testActiveProviderDetection(): void {
    $active_provider = $this->mockProvider(Environment::PRODUCTION, TRUE, id: 'active-provider');
    $inactive_provider = $this->mockProvider(Environment::LOCAL, FALSE, id: 'inactive-provider');

    Environment::init(providers: [$active_provider, $inactive_provider]);
    $this->assertSame(Environment::PRODUCTION, getenv('ENVIRONMENT_TYPE'));
  }

  public function testProviderReturningNullUsesFallback(): void {
    $null_provider = $this->mockProvider(NULL, TRUE, id: 'null-provider');

    Environment::init(fallback: Environment::PREVIEW, providers: [$null_provider]);
    $this->assertSame(Environment::PREVIEW, getenv('ENVIRONMENT_TYPE'));
  }

  public function testOverrideCallbackModifiesType(): void {
    $provider = $this->mockProvider(Environment::LOCAL, TRUE, id: 'override-test');
    $override = (fn($provider, $type): string => Environment::STAGE);

    Environment::init(override: $override, providers: [$provider]);
    $this->assertSame(Environment::STAGE, getenv('ENVIRONMENT_TYPE'));
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

  public function testReset(): void {
    Environment::init(fallback: Environment::STAGE);
    $this->assertSame(Environment::STAGE, getenv('ENVIRONMENT_TYPE'));

    Environment::reset();
    self::envUnset('ENVIRONMENT_TYPE');

    Environment::init();
    $this->assertSame(Environment::DEVELOPMENT, getenv('ENVIRONMENT_TYPE'));
  }

  public function testResetAll(): void {
    $override = (fn($provider, $type): string => Environment::PRODUCTION);
    Environment::init(fallback: Environment::STAGE, override: $override);
    $this->assertSame(Environment::PRODUCTION, getenv('ENVIRONMENT_TYPE'));

    Environment::reset(TRUE);
    self::envUnset('ENVIRONMENT_TYPE');

    Environment::init();
    $this->assertSame(Environment::DEVELOPMENT, getenv('ENVIRONMENT_TYPE'));
  }

  public function testMultipleActiveProvidersException(): void {
    $provider1 = $this->mockProvider(Environment::LOCAL, TRUE, id: 'active-id');
    $provider2 = $this->mockProvider(Environment::STAGE, TRUE, id: 'active-id-2');

    $this->expectException(\Exception::class);
    $this->expectExceptionMessageMatches('/Multiple active environment providers detected/');

    Environment::init(providers: [$provider1, $provider2]);
  }

  public function testMultipleActiveContextsException(): void {
    $context1 = $this->mockContext(TRUE, 'active-id');
    $context2 = $this->mockContext(TRUE, 'active-id-2');

    $this->expectException(\Exception::class);
    $this->expectExceptionMessageMatches('/Multiple active contexts detected/');

    Environment::init(contexts: [$context1, $context2]);
  }

  public function testInitWithInvalidOverrideCallback(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('The callback must be callable');

    Environment::init(override: ['not', 'callable']);
  }

  public function testInitWithInvalidProvider(): void {
    $invalid_provider = new \stdClass();

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('The provider must implement ProviderInterface');

    // @phpstan-ignore-next-line
    Environment::init(providers: [$invalid_provider]);
  }

  public function testInitWithInvalidContext(): void {
    $invalid_context = new \stdClass();

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('The context must implement ContextInterface');

    // @phpstan-ignore-next-line
    Environment::init(contexts: [$invalid_context]);
  }

  public function testDuplicateProviderIdsException(): void {
    $provider1 = $this->mockProvider(Environment::LOCAL, FALSE, id: 'duplicate-id');
    $provider2 = $this->mockProvider(Environment::STAGE, FALSE, id: 'duplicate-id');

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Provider with ID "duplicate-id" is already registered');

    Environment::init(providers: [$provider1, $provider2]);
  }

  public function testDuplicateContextIdsException(): void {
    $context1 = $this->mockContext(FALSE, 'duplicate-id');
    $context2 = $this->mockContext(FALSE, 'duplicate-id');

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Context with ID "duplicate-id" is already registered');

    Environment::init(contexts: [$context1, $context2]);
  }

  #[DataProvider('dataProviderContextualization')]
  public function testContextualization(bool $contextualize, bool $has_active_context, int $expected_contextualize_calls, int $expected_provider_contextualize_calls): void {
    $mock_context = $this->mockContext($has_active_context, 'test-context');
    $mock_provider = $this->mockProvider(Environment::STAGE, TRUE, id: 'test-provider');

    if ($has_active_context && $contextualize) {
      // @phpstan-ignore-next-line
      $mock_context->expects($this->exactly($expected_contextualize_calls))
        ->method('contextualize');
      // @phpstan-ignore-next-line
      $mock_provider->expects($this->exactly($expected_provider_contextualize_calls))
        ->method('contextualize')
        ->with($mock_context);
    }
    else {
      // @phpstan-ignore-next-line
      $mock_context->expects($this->exactly($expected_contextualize_calls))
        ->method('contextualize');
      // @phpstan-ignore-next-line
      $mock_provider->expects($this->exactly($expected_provider_contextualize_calls))
        ->method('contextualize');
    }

    Environment::init(
      contextualize: $contextualize,
      providers: [$mock_provider],
      contexts: [$mock_context]
    );

    $this->assertSame(Environment::STAGE, getenv('ENVIRONMENT_TYPE'));
  }

  public static function dataProviderContextualization(): \Iterator {
    yield 'contextualize true with active context' => [TRUE, TRUE, 1, 1];
    yield 'contextualize false with active context' => [FALSE, TRUE, 0, 0];
    yield 'contextualize true without active context' => [TRUE, FALSE, 0, 0];
  }

  #[DataProvider('dataProviderProviderTypes')]
  public function testProviderTypes(?string $provider_type, string $fallback, string $expected): void {
    $provider = $this->mockProvider($provider_type, TRUE, id: 'type-test-provider');

    Environment::init(fallback: $fallback, providers: [$provider]);

    $this->assertSame($expected, getenv('ENVIRONMENT_TYPE'));
  }

  public static function dataProviderProviderTypes(): \Iterator {
    yield 'provider returns local' => [Environment::LOCAL, Environment::DEVELOPMENT, Environment::LOCAL];
    yield 'provider returns ci' => [Environment::CI, Environment::DEVELOPMENT, Environment::CI];
    yield 'provider returns development' => [Environment::DEVELOPMENT, Environment::STAGE, Environment::DEVELOPMENT];
    yield 'provider returns preview' => [Environment::PREVIEW, Environment::DEVELOPMENT, Environment::PREVIEW];
    yield 'provider returns stage' => [Environment::STAGE, Environment::DEVELOPMENT, Environment::STAGE];
    yield 'provider returns production' => [Environment::PRODUCTION, Environment::DEVELOPMENT, Environment::PRODUCTION];
    yield 'provider returns null uses fallback' => [NULL, Environment::PREVIEW, Environment::PREVIEW];
  }

  public function testOverrideCallbackReceivesCorrectParameters(): void {
    $received_provider = NULL;
    $received_type = NULL;
    $active_provider = $this->mockProvider(Environment::LOCAL, TRUE, id: 'callback-test-provider');

    $override = function ($provider, $type) use (&$received_provider, &$received_type): string {
      $received_provider = $provider;
      $received_type = $type;
      return Environment::PRODUCTION;
    };

    Environment::init(override: $override, providers: [$active_provider]);

    $this->assertSame($active_provider, $received_provider);
    $this->assertEquals(Environment::LOCAL, $received_type);
    $this->assertSame(Environment::PRODUCTION, getenv('ENVIRONMENT_TYPE'));
  }

}
