<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests;

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

  protected function mockProvider(string|callable|null $type, string $id = 'mock'): ProviderInterface {
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

  public function testDuplicatedProviders(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Provider with ID "mock" is already registered');

    Environment::reset();
    Environment::addProvider($this->mockProvider('mock'));
    Environment::addProvider($this->mockProvider('mock'));
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

}
