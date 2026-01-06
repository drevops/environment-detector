<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Providers;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Providers\GitLabCi;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(GitLabCi::class)]
#[CoversClass(Environment::class)]
final class GitLabCiTest extends ProviderTestCase {

  protected function setUp(): void {
    parent::setUp();

    $this->providerId = 'gitlab_ci';
  }

  public static function dataProviderActive(): \Iterator|array {
    yield [fn(): null => NULL, FALSE];
    yield [fn() => self::envSet('GITLAB_CI', 'TRUE'), TRUE];
  }

  public static function dataProviderData(): \Iterator|array {
    yield [
      fn(): null => NULL,
      NULL,
    ];
    yield [
      fn() => self::envSet('GITLAB_CI', 'TRUE'),
        ['GITLAB_CI' => 'TRUE'],
    ];
    yield [
      function (): void {
          self::envSet('GITLAB_CI', 'TRUE');
          self::envSet('CI_REPOSITORY_URL', 'abc');
          self::envSet('OTHER_VAR', 'other_val');
      },
        [
          'GITLAB_CI' => 'TRUE',
          'CI_REPOSITORY_URL' => 'abc',
        ],
    ];
  }

  public static function dataProviderType(): \Iterator|array {
    yield [
      fn(): null => NULL,
      NULL,
    ];
    yield [
      fn() => self::envSet('GITLAB_CI', 'TRUE'),
      Environment::CI,
      function ($test): void {
          $test->assertTrue(Environment::isCi());
      },
    ];
  }

}
