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
class EnvironmentTest extends EnvironmentDetectorTestCase {

  public function testConstants(): void {
    $this->assertEquals('local', Environment::LOCAL);
    $this->assertEquals('ci', Environment::CI);
    $this->assertEquals('development', Environment::DEVELOPMENT);
    $this->assertEquals('preview', Environment::PREVIEW);
    $this->assertEquals('stage', Environment::STAGE);
    $this->assertEquals('production', Environment::PRODUCTION);
  }

  #[DataProvider('dataProviderEnvironmentTypeDetection')]
  public function testEnvironmentTypeDetection(?string $env_var, string $expected, array $providers, ?callable $override, string $fallback): void {
    if ($env_var !== NULL) {
      static::envSet('ENVIRONMENT_TYPE', $env_var);
    }

    Environment::init(
      contextualize: FALSE,
      fallback: $fallback,
      override: $override,
      providers: $providers
    );

    $this->assertEquals($expected, getenv('ENVIRONMENT_TYPE'));
  }

  public static function dataProviderEnvironmentTypeDetection(): array {
    return [
      'pre-set env var' => [
        Environment::PRODUCTION,
        Environment::PRODUCTION,
        [],
        NULL,
        Environment::DEVELOPMENT,
      ],
      'fallback when no providers' => [
        NULL,
        Environment::PREVIEW,
        [],
        NULL,
        Environment::PREVIEW,
      ],
      'override callback changes type' => [
        NULL,
        Environment::CI,
        [],
        fn($provider, $type): string => Environment::CI,
        Environment::DEVELOPMENT,
      ],
      'override callback returning null uses fallback' => [
        NULL,
        Environment::STAGE,
        [],
        fn($provider, $type): null => NULL,
        Environment::STAGE,
      ],
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

    $this->assertEquals($expected, getenv('ENVIRONMENT_TYPE'));
  }

  public static function dataProviderInitWithParameterCombinations(): array {
    return [
      'default parameters' => [
        TRUE,
        Environment::DEVELOPMENT,
        NULL,
        [],
        [],
        Environment::DEVELOPMENT,
      ],
      'contextualize false' => [
        FALSE,
        Environment::DEVELOPMENT,
        NULL,
        [],
        [],
        Environment::DEVELOPMENT,
      ],
      'custom fallback' => [
        TRUE,
        Environment::PRODUCTION,
        NULL,
        [],
        [],
        Environment::PRODUCTION,
      ],
    ];
  }

  public function testInitOnlyRunsOnce(): void {
    Environment::init(fallback: Environment::STAGE);
    $this->assertEquals(Environment::STAGE, getenv('ENVIRONMENT_TYPE'));

    Environment::init(fallback: Environment::PRODUCTION);
    $this->assertEquals(Environment::STAGE, getenv('ENVIRONMENT_TYPE'));
  }

  public function testActiveProviderDetection(): void {
    $active_provider = $this->mockProvider(Environment::PRODUCTION, TRUE, id: 'active-provider');
    $inactive_provider = $this->mockProvider(Environment::LOCAL, FALSE, id: 'inactive-provider');

    Environment::init(providers: [$active_provider, $inactive_provider]);
    $this->assertEquals(Environment::PRODUCTION, getenv('ENVIRONMENT_TYPE'));
  }

  public function testProviderReturningNullUsesFallback(): void {
    $null_provider = $this->mockProvider(NULL, TRUE, id: 'null-provider');

    Environment::init(fallback: Environment::PREVIEW, providers: [$null_provider]);
    $this->assertEquals(Environment::PREVIEW, getenv('ENVIRONMENT_TYPE'));
  }

  public function testOverrideCallbackModifiesType(): void {
    $provider = $this->mockProvider(Environment::LOCAL, TRUE, id: 'override-test');
    $override = function ($provider, $type): string {
      return Environment::STAGE;
    };

    Environment::init(override: $override, providers: [$provider]);
    $this->assertEquals(Environment::STAGE, getenv('ENVIRONMENT_TYPE'));
  }

  #[DataProvider('dataProviderIsEnvironmentTypeMethods')]
  public function testIsEnvironmentTypeMethods(string $env_type, array $expected_results): void {
    static::envSet('ENVIRONMENT_TYPE', $env_type);

    $this->assertEquals($expected_results['isLocal'], Environment::isLocal());
    $this->assertEquals($expected_results['isCi'], Environment::isCi());
    $this->assertEquals($expected_results['isDev'], Environment::isDev());
    $this->assertEquals($expected_results['isPreview'], Environment::isPreview());
    $this->assertEquals($expected_results['isStage'], Environment::isStage());
    $this->assertEquals($expected_results['isProd'], Environment::isProd());
  }

  public static function dataProviderIsEnvironmentTypeMethods(): array {
    return [
      'local environment' => [
        Environment::LOCAL,
        [
          'isLocal' => TRUE,
          'isCi' => FALSE,
          'isDev' => FALSE,
          'isPreview' => FALSE,
          'isStage' => FALSE,
          'isProd' => FALSE,
        ],
      ],
      'ci environment' => [
        Environment::CI,
        [
          'isLocal' => FALSE,
          'isCi' => TRUE,
          'isDev' => FALSE,
          'isPreview' => FALSE,
          'isStage' => FALSE,
          'isProd' => FALSE,
        ],
      ],
      'development environment' => [
        Environment::DEVELOPMENT,
        [
          'isLocal' => FALSE,
          'isCi' => FALSE,
          'isDev' => TRUE,
          'isPreview' => FALSE,
          'isStage' => FALSE,
          'isProd' => FALSE,
        ],
      ],
      'preview environment' => [
        Environment::PREVIEW,
        [
          'isLocal' => FALSE,
          'isCi' => FALSE,
          'isDev' => FALSE,
          'isPreview' => TRUE,
          'isStage' => FALSE,
          'isProd' => FALSE,
        ],
      ],
      'stage environment' => [
        Environment::STAGE,
        [
          'isLocal' => FALSE,
          'isCi' => FALSE,
          'isDev' => FALSE,
          'isPreview' => FALSE,
          'isStage' => TRUE,
          'isProd' => FALSE,
        ],
      ],
      'production environment' => [
        Environment::PRODUCTION,
        [
          'isLocal' => FALSE,
          'isCi' => FALSE,
          'isDev' => FALSE,
          'isPreview' => FALSE,
          'isStage' => FALSE,
          'isProd' => TRUE,
        ],
      ],
    ];
  }

  #[DataProvider('dataProviderIsMethodWithCustomTypes')]
  public function testIsMethodWithCustomTypes(string $env_type, string $test_type, bool $expected): void {
    static::envSet('ENVIRONMENT_TYPE', $env_type);
    $this->assertEquals($expected, Environment::is($test_type));
  }

  public static function dataProviderIsMethodWithCustomTypes(): array {
    return [
      'custom type matches' => ['custom-env', 'custom-env', TRUE],
      'custom type does not match' => ['custom-env', 'different-env', FALSE],
      'standard type matches custom' => [Environment::LOCAL, Environment::LOCAL, TRUE],
      'standard type does not match custom' => [Environment::LOCAL, 'custom-env', FALSE],
    ];
  }

  public function testReset(): void {
    Environment::init(fallback: Environment::STAGE);
    $this->assertEquals(Environment::STAGE, getenv('ENVIRONMENT_TYPE'));

    Environment::reset();
    static::envUnset('ENVIRONMENT_TYPE');

    Environment::init();
    $this->assertEquals(Environment::DEVELOPMENT, getenv('ENVIRONMENT_TYPE'));
  }

  public function testResetAll(): void {
    $override = function ($provider, $type): string {
      return Environment::PRODUCTION;
    };
    Environment::init(fallback: Environment::STAGE, override: $override);
    $this->assertEquals(Environment::PRODUCTION, getenv('ENVIRONMENT_TYPE'));

    Environment::reset(TRUE);
    static::envUnset('ENVIRONMENT_TYPE');

    Environment::init();
    $this->assertEquals(Environment::DEVELOPMENT, getenv('ENVIRONMENT_TYPE'));
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

    $this->assertEquals(Environment::STAGE, getenv('ENVIRONMENT_TYPE'));
  }

  public static function dataProviderContextualization(): array {
    return [
      'contextualize true with active context' => [TRUE, TRUE, 1, 1],
      'contextualize false with active context' => [FALSE, TRUE, 0, 0],
      'contextualize true without active context' => [TRUE, FALSE, 0, 0],
    ];
  }

  #[DataProvider('dataProviderProviderTypes')]
  public function testProviderTypes(?string $provider_type, string $fallback, string $expected): void {
    $provider = $this->mockProvider($provider_type, TRUE, id: 'type-test-provider');

    Environment::init(fallback: $fallback, providers: [$provider]);

    $this->assertEquals($expected, getenv('ENVIRONMENT_TYPE'));
  }

  public static function dataProviderProviderTypes(): array {
    return [
      'provider returns local' => [Environment::LOCAL, Environment::DEVELOPMENT, Environment::LOCAL],
      'provider returns ci' => [Environment::CI, Environment::DEVELOPMENT, Environment::CI],
      'provider returns development' => [Environment::DEVELOPMENT, Environment::STAGE, Environment::DEVELOPMENT],
      'provider returns preview' => [Environment::PREVIEW, Environment::DEVELOPMENT, Environment::PREVIEW],
      'provider returns stage' => [Environment::STAGE, Environment::DEVELOPMENT, Environment::STAGE],
      'provider returns production' => [Environment::PRODUCTION, Environment::DEVELOPMENT, Environment::PRODUCTION],
      'provider returns null uses fallback' => [NULL, Environment::PREVIEW, Environment::PREVIEW],
    ];
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
    $this->assertEquals(Environment::PRODUCTION, getenv('ENVIRONMENT_TYPE'));
  }

}
