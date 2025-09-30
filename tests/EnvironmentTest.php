<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests;

use DrevOps\EnvironmentDetector\Contexts\AbstractContext;
use DrevOps\EnvironmentDetector\Contexts\ContextInterface;
use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Providers\AbstractProvider;
use DrevOps\EnvironmentDetector\Providers\ProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Environment::class)]
#[CoversClass(AbstractProvider::class)]
#[CoversClass(AbstractContext::class)]
class EnvironmentTest extends TestBase {

  public function testInitDefault(): void {
    Environment::addProvider($this->mockProvider(Environment::STAGE, TRUE, function (): void {
      static::envSet('TEST_VAR_PROVIDER', 'test_val_provider1');
    }, id: 'provider1'));
    Environment::addProvider($this->mockProvider(Environment::STAGE, FALSE, function (): void {
      static::envSet('TEST_VAR_PROVIDER', 'test_val_provider2');
    }, id: 'provider2'));

    // Context will set an environment variable.
    Environment::addContext($this->mockContext(TRUE, 'mocked_context', function (): void {
      static::envSet('TEST_VAR_CONTEXT', 'test_val_context');
    }));

    $this->assertEmpty(static::envGet('TEST_VAR_CONTEXT'), 'Context variable is not set');
    $this->assertEmpty(static::envGet('TEST_VAR_PROVIDER'), 'Provider context variable is not set');

    Environment::init();

    $this->assertEquals(Environment::STAGE, static::envGet('ENVIRONMENT_TYPE'), 'Environment type is set via ENVIRONMENT_TYPE env variable');
    $this->assertEquals(Environment::STAGE, Environment::type(), 'Environment type is set within the type() method');
    $this->assertEquals('test_val_context', static::envGet('TEST_VAR_CONTEXT'), 'Context is applied');
    $this->assertEquals('test_val_provider1', static::envGet('TEST_VAR_PROVIDER'), 'Provider context is applied');
  }

  public function testInitTypeFromEnvVar(): void {
    // Provider will return the STAGE environment type.
    Environment::addProvider($this->mockProvider(Environment::STAGE, contextualize: function (): void {
      static::envSet('TEST_VAR_PROVIDER', 'test_val_provider');
    }));

    // Context will set an environment variable.
    Environment::addContext($this->mockContext(TRUE, 'mocked_context', function (): void {
      static::envSet('TEST_VAR_CONTEXT', 'test_val_context');
    }));

    $this->assertEmpty(static::envGet('TEST_VAR_CONTEXT'), 'Context variable is not set');
    $this->assertEmpty(static::envGet('TEST_VAR_PROVIDER'), 'Provider context variable is not set');

    // Set the environment type via an environment variable. This should
    // override the environment type discovered by the provider.
    static::envSet('ENVIRONMENT_TYPE', Environment::PREVIEW);

    Environment::init();

    $this->assertEquals(Environment::PREVIEW, static::envGet('ENVIRONMENT_TYPE'), 'Environment type is set via ENVIRONMENT_TYPE env variable');
    $this->assertEquals(Environment::PREVIEW, Environment::type(), 'Environment type is set within the type() method');
    $this->assertEquals('test_val_context', static::envGet('TEST_VAR_CONTEXT'), 'Context is applied');
    // This is the main assertion: although we did not perform a provider-based
    // type detection, the provider context was still applied.
    $this->assertEquals('test_val_provider', static::envGet('TEST_VAR_PROVIDER'), 'Provider context is applied');
  }

  public function testIs(): void {
    Environment::reset();
    Environment::addProvider($this->mockProvider('custom'));

    $this->assertSame('custom', Environment::type());
    $this->assertTrue(Environment::is('custom'));
  }

  public function testIsTypes(): void {
    Environment::reset();
    static::envUnset('ENVIRONMENT_TYPE');
    Environment::addProvider($this->mockProvider(Environment::LOCAL));
    $this->assertEquals(Environment::LOCAL, Environment::isLocal());
    $this->assertEquals(Environment::LOCAL, static::envGet('ENVIRONMENT_TYPE'));
    $this->assertEquals(Environment::LOCAL, Environment::is(Environment::LOCAL));

    Environment::reset();
    static::envUnset('ENVIRONMENT_TYPE');
    Environment::addProvider($this->mockProvider(Environment::CI));
    $this->assertEquals(Environment::CI, Environment::isCi());
    $this->assertEquals(Environment::CI, Environment::is(Environment::CI));

    Environment::reset();
    static::envUnset('ENVIRONMENT_TYPE');
    Environment::addProvider($this->mockProvider(Environment::DEVELOPMENT));
    $this->assertEquals(Environment::DEVELOPMENT, Environment::isDev());
    $this->assertEquals(Environment::DEVELOPMENT, Environment::is(Environment::DEVELOPMENT));

    Environment::reset();
    static::envUnset('ENVIRONMENT_TYPE');
    Environment::addProvider($this->mockProvider(Environment::STAGE));
    $this->assertEquals(Environment::STAGE, Environment::isStage());
    $this->assertEquals(Environment::STAGE, Environment::is(Environment::STAGE));

    Environment::reset();
    static::envUnset('ENVIRONMENT_TYPE');
    Environment::addProvider($this->mockProvider(Environment::PRODUCTION));
    $this->assertEquals(Environment::PRODUCTION, Environment::isProd());
    $this->assertEquals(Environment::PRODUCTION, Environment::is(Environment::PRODUCTION));
  }

  public function testOverrideCallbackChangesEnvironmentType(): void {
    Environment::reset();
    Environment::addProvider($this->mockProvider());

    Environment::setOverride(function (ProviderInterface $provider, string $original): string {
      return Environment::PRODUCTION;
    });

    $this->assertSame(Environment::PRODUCTION, Environment::type());
  }

  public function testOverrideCallbackChangesEnvironmentTypeBasedOnLogic(): void {
    Environment::reset();
    Environment::addProvider($this->mockProvider(function () {
      return getenv('TEST_ENVIRONMENT_TYPE') ?: Environment::DEVELOPMENT;
    }));

    $this->assertEquals(Environment::DEVELOPMENT, Environment::type(), 'Default environment type is DEV');

    Environment::reset(FALSE);
    static::envUnset('ENVIRONMENT_TYPE');
    static::envSet('TEST_ENVIRONMENT_TYPE', Environment::STAGE);
    $this->assertEquals(Environment::STAGE, Environment::type(), 'Type is taken from ENVIRONMENT_TYPE env variable');

    Environment::reset(FALSE);
    static::envUnset('ENVIRONMENT_TYPE');
    Environment::setOverride(function (ProviderInterface $provider, string $original): string {
      return Environment::PRODUCTION;
    });
    $this->assertEquals(Environment::PRODUCTION, Environment::type(), 'Type is overridden by callback');

    Environment::reset(FALSE);
    static::envUnset('ENVIRONMENT_TYPE');
    Environment::setOverride(function (ProviderInterface $provider, string $original): string {
      return $original === Environment::STAGE ? Environment::CI : $original;
    });
    $this->assertEquals(Environment::CI, Environment::type(), 'Type is overridden by callback based on original value');
  }

  public function testOverrideCallbackNotCallable(): void {
    $this->expectException(\InvalidArgumentException::class);
    Environment::reset();
    $provider = $this->mockProvider();
    Environment::addProvider($provider);
    // @phpstan-ignore-next-line
    Environment::setOverride([$provider, 'not_callable']);
  }

  public function testFallbackTypeCanBeChanged(): void {
    Environment::reset();
    Environment::setFallback(Environment::STAGE);
    Environment::addProvider($this->mockProvider(NULL));

    $this->assertSame(Environment::STAGE, Environment::type());
    $this->assertSame(Environment::STAGE, Environment::fallback());
  }

  public function testProvidersAlwaysAvailable(): void {
    // With the optimization, built-in providers are always available from constants.
    Environment::reset();
    $providers = Environment::providers();

    // Verify that built-in providers are loaded.
    $this->assertNotEmpty($providers);
    $this->assertGreaterThan(0, count($providers));
  }

  public function testProvidersNoDuplicated(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Provider with ID "provider1" is already registered');

    Environment::reset();
    Environment::addProvider($this->mockProvider(id: 'provider1'));
    Environment::addProvider($this->mockProvider(id: 'provider1'));
  }

  public function testProviderActiveOnlyOne(): void {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Multiple active environment providers detected');

    Environment::reset();
    Environment::addProvider($this->mockProvider(type: 'provider1', id: 'provider1'));
    Environment::addProvider($this->mockProvider(type: 'provider2', id: 'provider2'));

    $this->assertSame('dev', Environment::type());
  }

  public function testProviderDataPrefixes(): void {
    static::envSetMultiple([
      'TEST_VAR_PROVIDER' => 'test_val_provider',
    ]);
    Environment::addProvider($this->mockProvider(type: 'provider1', env_prefixes: function (): array {
      return ['TEST_VAR_'];
    }));

    Environment::init();
    $this->assertEquals(['TEST_VAR_PROVIDER' => 'test_val_provider'], Environment::provider()?->data());
  }

  public function testProviderDataEmptyPrefixes(): void {
    static::envSetMultiple([
      'TEST_VAR_PROVIDER' => 'test_val_provider',
    ]);
    Environment::addProvider($this->mockProvider(type: 'provider1', env_prefixes: function (): array {
      return [];
    }));

    Environment::init();
    $this->assertEquals([], Environment::provider()?->data());
  }

  public function testProviderAdditionResetsCache(): void {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Multiple active environment providers detected');

    Environment::reset();
    Environment::addProvider($this->mockProvider(Environment::STAGE, id: 'provider1'));
    $this->assertSame(Environment::STAGE, Environment::type());

    static::envUnset('ENVIRONMENT_TYPE');

    // Add another provider, which should reset the cache and trigger an
    // exception because there are multiple active providers.
    Environment::addProvider($this->mockProvider(Environment::STAGE, id: 'provider2'));
    $this->assertSame(Environment::STAGE, Environment::type());
  }

  public function testContextGeneric(): void {
    Environment::reset();

    Environment::addProvider($this->mockProvider(contextualize: function (ContextInterface $context): void {
      global $arg1;
      global $arg2;

      if (is_array($arg1) && is_array($arg2)) {
        $arg1['key2'] = 'value2';
        $arg2['key2'] = 'value2';
      }
    }));

    Environment::addContext($this->mockContext(function (): bool {
      global $arg1;
      global $arg2;
      return !empty($arg1['key1']) && !empty($arg2['key1']);
    }));

    global $arg1;
    global $arg2;

    Environment::init();
    $this->assertEquals(NULL, $arg1, 'Context is not applied to arg1');
    $this->assertEquals(NULL, $arg2, 'Context is not applied to arg2');

    $arg1 = ['key1' => 'value1'];
    $arg2 = ['key1' => 'value1'];
    Environment::init();
    $this->assertEquals(['key1' => 'value1', 'key2' => 'value2'], $arg1, 'Context is applied to arg1');
    $this->assertEquals(['key1' => 'value1', 'key2' => 'value2'], $arg2, 'Context is applied to arg2');
  }

  public function testContextsAlwaysAvailable(): void {
    // With the optimization, built-in contexts are always available from constants.
    Environment::reset();
    $contexts = Environment::contexts();

    // Verify that built-in contexts are loaded.
    $this->assertNotEmpty($contexts);
    $this->assertGreaterThan(0, count($contexts));
  }

  public function testContextsNoDuplicated(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Context with ID "context1" is already registered');

    Environment::reset();
    Environment::addProvider($this->mockProvider());
    Environment::addContext($this->mockContext(TRUE, 'context1'));
    Environment::addContext($this->mockContext(TRUE, 'context1'));

    Environment::init();
  }

  public function testContextActiveOnlyOne(): void {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Multiple active contexts detected');

    Environment::reset();
    Environment::addProvider($this->mockProvider());
    Environment::addContext($this->mockContext(id: 'context1'));
    Environment::addContext($this->mockContext(id: 'context2'));

    Environment::init();
  }

  public function testCannotInstantiateConstructor(): void {
    $this->expectException(\Error::class);
    // @phpstan-ignore-next-line
    new Environment();
  }

}
