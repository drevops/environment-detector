<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Providers;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Providers\GitHubActions;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(GitHubActions::class)]
#[CoversClass(Environment::class)]
class GitHubActionsTest extends ProviderTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->providerId = 'github_actions';
  }

  public static function dataProviderActive(): array {
    return [
      [fn(): null => NULL, FALSE],
      [fn() => static::envSet('GITHUB_WORKFLOW', 'workflow_name'), TRUE],
    ];
  }

  public static function dataProviderData(): array {
    return [
      [
        fn(): null => NULL,
        NULL,
      ],
      [
        fn() => static::envSet('GITHUB_WORKFLOW', 'workflow_name'),
        ['GITHUB_WORKFLOW' => 'workflow_name'],
      ],
      [
        function (): void {
          static::envSet('GITHUB_WORKFLOW', 'workflow_name');
          static::envSet('GITHUB_WORKFLOW_REF', 'abc');
          static::envSet('OTHER_VAR', 'other_val');
        },
        [
          'GITHUB_WORKFLOW' => 'workflow_name',
          'GITHUB_WORKFLOW_REF' => 'abc',
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
        fn() => static::envSet('GITHUB_WORKFLOW', 'workflow_name'),
        Environment::CI,
        function ($test): void {
          $test->assertTrue(Environment::isCi());
        },
      ],
    ];
  }

}
