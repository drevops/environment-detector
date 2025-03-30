<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests;

use DrevOps\EnvironmentDetector\Contexts\ContextInterface;
use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Providers\ProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Environment::class)]
class EnvironmentTest extends TestCase {

  use EnvTrait;

  public function testOverrideCallbackChangesEnvironmentType(): void {
    Environment::reset();
    Environment::addProvider($this->mockProvider(Environment::DEVELOPMENT));

    Environment::setOverride(function (ProviderInterface $provider, string $original): string {
      return Environment::PRODUCTION;
    });

    $this->assertSame(Environment::PRODUCTION, Environment::type());
  }

  public function testOverrideCallbackChangesEnvironmentTypeBasedOnLogic(): void {
    Environment::reset();
    Environment::addProvider($this->mockProvider(function () {
      return getenv('ENVIRONMENT_TYPE') ?: Environment::DEVELOPMENT;
    }));

    $this->assertEquals(Environment::DEVELOPMENT, Environment::type(), 'Default environment type is DEV');

    Environment::reset(FALSE);
    static::envSet('ENVIRONMENT_TYPE', Environment::STAGE);
    $this->assertEquals(Environment::STAGE, Environment::type(), 'Type is taken from ENVIRONMENT_TYPE env variable');

    Environment::reset(FALSE);
    Environment::setOverride(function (ProviderInterface $provider, string $original): string {
      return Environment::PRODUCTION;
    });
    $this->assertEquals(Environment::PRODUCTION, Environment::type(), 'Type is overridden by callback');

    Environment::reset(FALSE);
    Environment::setOverride(function (ProviderInterface $provider, string $original): string {
      return $original === Environment::STAGE ? Environment::CI : $original;
    });
    $this->assertEquals(Environment::CI, Environment::type(), 'Type is overridden by callback based on original value');
  }

  public function testOverrideCallbackNotCallable(): void {
    $this->expectException(\InvalidArgumentException::class);
    Environment::reset();
    $provider = $this->mockProvider(Environment::DEVELOPMENT);
    Environment::addProvider($provider);
    Environment::setOverride([$provider, 'not_callable']);
  }

  public function testIs(): void {
    Environment::reset();
    Environment::addProvider($this->mockProvider('custom'));

    $this->assertSame('custom', Environment::type());
    $this->assertTrue(Environment::is('custom'));
  }

  public function testNoProviders(): void {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('No environment providers were registered');

    Environment::reset();
    Environment::providers(['default' => 'non-exiting-dir']);
  }

  public function testProviderOnlyOneActive(): void {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Multiple active environment providers detected');

    Environment::reset();
    Environment::addProvider($this->mockProvider('dev', 'provider1'));
    Environment::addProvider($this->mockProvider('dev', 'provider2'));

    $this->assertSame('dev', Environment::type());
  }

  public function testAddProviderResetsCache(): void {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Multiple active environment providers detected');

    Environment::reset();
    Environment::addProvider($this->mockProvider('dev', 'provider1'));
    $this->assertSame('dev', Environment::type());

    // Add another provider, which should reset the cache and trigger an
    // exception because there are multiple active providers.
    Environment::addProvider($this->mockProvider('dev', 'provider2'));
    $this->assertSame('dev', Environment::type());
  }

  public function testFallbackTypeCanBeChanged(): void {
    Environment::reset();
    Environment::setFallback('dev');
    Environment::addProvider($this->mockProvider(NULL));

    $this->assertSame('dev', Environment::type());
    $this->assertSame('dev', Environment::fallback());
  }

  public function testDuplicatedProviders(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Provider with ID "mocked_provider" is already registered');

    Environment::reset();
    Environment::addProvider($this->mockProvider('mocked_provider'));
    Environment::addProvider($this->mockProvider('mocked_provider'));
  }

  public function testEnvironmentIsTypes(): void {
    Environment::reset();
    Environment::addProvider($this->mockProvider(Environment::LOCAL));
    $this->assertEquals(Environment::LOCAL, Environment::isLocal());
    $this->assertEquals(Environment::LOCAL, Environment::is(Environment::LOCAL));

    Environment::reset();
    Environment::addProvider($this->mockProvider(Environment::CI));
    $this->assertEquals(Environment::CI, Environment::isCi());
    $this->assertEquals(Environment::CI, Environment::is(Environment::CI));

    Environment::reset();
    Environment::addProvider($this->mockProvider(Environment::DEVELOPMENT));
    $this->assertEquals(Environment::DEVELOPMENT, Environment::isDev());
    $this->assertEquals(Environment::DEVELOPMENT, Environment::is(Environment::DEVELOPMENT));

    Environment::reset();
    Environment::addProvider($this->mockProvider(Environment::STAGE));
    $this->assertEquals(Environment::STAGE, Environment::isStage());
    $this->assertEquals(Environment::STAGE, Environment::is(Environment::STAGE));

    Environment::reset();
    Environment::addProvider($this->mockProvider(Environment::PRODUCTION));
    $this->assertEquals(Environment::PRODUCTION, Environment::isProd());
    $this->assertEquals(Environment::PRODUCTION, Environment::is(Environment::PRODUCTION));
  }

  public function testCannotInstantiateConstructor(): void {
    $this->expectException(\Error::class);
    // @phpstan-ignore-next-line
    new Environment();
  }

  public function testNoContexts(): void {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('No contexts were registered');

    Environment::reset();
    Environment::contexts(['default' => 'non-exiting-dir']);
  }

  public function testContextGeneric(): void {
    Environment::reset();

    $provider = $this->mockProvider(Environment::DEVELOPMENT);

    // Override the applyContext method to add some processing. PHPUnit does not
    // allow to mock the dynamic applyContext<Name> method.
    // This callback will be applied after the context is applied, so this
    // will run after the activation callback in the context mock below.
    // @phpstan-ignore-next-line
    $provider->method('applyContext')->willReturnCallback(function (ContextInterface $context, ?array &$data = NULL): void {
      $arg1 = &$data['arg1'];
      $arg2 = &$data['arg2'];

      if (is_array($arg1) && is_array($arg2)) {
        $arg1['key2'] = 'value2';
        $arg2['key2'] = 'value2';
      }
    });
    Environment::addProvider($provider);

    // Add a context that will activate only if both arg1 and arg2 are arrays.
    Environment::addContext($this->mockContext(function (?array $data = NULL): bool {
      $arg1 = $data['arg1'] ?? [];
      $arg2 = $data['arg2'] ?? [];

      if (is_array($arg1) && is_array($arg2)) {
        return !empty($arg1['key1']) && !empty($arg2['key1']);
      }

      return FALSE;
    }));

    Environment::applyContext();
    $this->assertEquals(
      [],
      [],
      'Context is not applied when no data is passed'
    );

    $data = ['arg1' => [], 'arg2' => []];
    Environment::applyContext($data);
    $this->assertEquals(
      ['arg1' => [], 'arg2' => []],
      $data,
      'Context is not applied'
    );

    $data = ['arg1' => ['key1' => 'value1'], 'arg2' => ['key1' => 'value1']];
    Environment::applyContext($data);
    $this->assertEquals(
      ['arg1' => ['key1' => 'value1', 'key2' => 'value2'], 'arg2' => ['key1' => 'value1', 'key2' => 'value2']],
      $data,
      'Context is applied'
    );
  }

  protected function mockProvider(string|callable|null $type, string $id = 'mocked_provider'): ProviderInterface {
    $mock = $this->createMock(ProviderInterface::class);
    $mock->method('active')->willReturn(TRUE);

    if (is_callable($type)) {
      $mock->method('type')->willReturnCallback($type);
    }
    else {
      $mock->method('type')->willReturn($type);
    }

    $mock->method('id')->willReturn($id);

    return $mock;
  }

  protected function mockContext(string|callable|null $activate, string $id = 'mocked_context'): ContextInterface {

    $mock = $this->createMock(ContextInterface::class);

    if (is_callable($activate)) {
      $mock->method('active')->willReturnCallback($activate);
    }
    else {
      $mock->method('active')->willReturn($activate);
    }

    $mock->method('id')->willReturn($id);

    return $mock;
  }

}
