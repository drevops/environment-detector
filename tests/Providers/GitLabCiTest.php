<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Providers;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Providers\GitLabCi;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(GitLabCi::class)]
#[CoversClass(Environment::class)]
class GitLabCiTest extends ProviderTestCase {

  protected function setUp(): void {
    parent::setUp();

    $this->providerId = 'gitlab_ci';
  }

  public static function dataProviderActive(): array {
    return [
      [fn(): null => NULL, FALSE],
      [fn() => static::envSet('GITLAB_CI', 'TRUE'), TRUE],
    ];
  }

  public static function dataProviderData(): array {
    return [
      [
        fn(): null => NULL,
        NULL,
      ],
      [
        fn() => static::envSet('GITLAB_CI', 'TRUE'),
        ['GITLAB_CI' => 'TRUE'],
      ],
      [
        function (): void {
          static::envSet('GITLAB_CI', 'TRUE');
          static::envSet('CI_REPOSITORY_URL', 'abc');
          static::envSet('OTHER_VAR', 'other_val');
        },
        [
          'GITLAB_CI' => 'TRUE',
          'CI_REPOSITORY_URL' => 'abc',
        ],
      ],
    ];
  }

  public static function dataProviderType(): array {
    return [
      [
        fn(): null => NULL,
        NULL,
      ],
      [
        fn() => static::envSet('GITLAB_CI', 'TRUE'),
        Environment::CI,
        function ($test): void {
          $test->assertTrue(Environment::isCi());
        },
      ],
    ];
  }

}
