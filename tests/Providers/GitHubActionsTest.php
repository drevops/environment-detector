<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Providers;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Providers\GitHubActions;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(GitHubActions::class)]
#[CoversClass(Environment::class)]
final class GitHubActionsTest extends ProviderTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->providerId = 'github_actions';
  }

  public static function dataProviderActive(): \Iterator|array {
    yield [fn(): null => NULL, FALSE];
    yield [fn() => self::envSet('GITHUB_WORKFLOW', 'workflow_name'), TRUE];
  }

  public static function dataProviderData(): \Iterator|array {
    yield [
      fn(): null => NULL,
      NULL,
    ];
    yield [
      fn() => self::envSet('GITHUB_WORKFLOW', 'workflow_name'),
        ['GITHUB_WORKFLOW' => 'workflow_name'],
    ];
    yield [
      function (): void {
          self::envSet('GITHUB_WORKFLOW', 'workflow_name');
          self::envSet('GITHUB_WORKFLOW_REF', 'abc');
          self::envSet('OTHER_VAR', 'other_val');
      },
        [
          'GITHUB_WORKFLOW' => 'workflow_name',
          'GITHUB_WORKFLOW_REF' => 'abc',
        ],
    ];
  }

  public static function dataProviderType(): \Iterator|array {
    yield [
      fn(): null => NULL,
      NULL,
    ];
    yield [
      fn() => self::envSet('GITHUB_WORKFLOW', 'workflow_name'),
      Environment::CI,
      function ($test): void {
          $test->assertTrue(Environment::isCi());
      },
    ];
  }

}
