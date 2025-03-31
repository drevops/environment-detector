<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Providers;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Providers\Lagoon;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Lagoon::class)]
#[CoversClass(Environment::class)]
class LagoonTest extends ProviderTestBase {

  public static function dataProviderActive(): array {
    return [
      [fn(): null => NULL, FALSE],
      [fn() => static::envSet('LAGOON_KUBERNETES', 'myproject'), TRUE],
      [fn() => static::envSet('LAGOON_PROJECT', 'myproject'), FALSE],
    ];
  }

  public static function dataProviderData(): array {
    return [
      [
        fn(): null => NULL,
        NULL,
      ],
      [
        fn(): null => static::envSetMultiple([
          'LAGOON_PROJECT' => 'myproject',
        ]),
        NULL,
      ],

      [
        fn(): null => static::envSetMultiple([
          'LAGOON_KUBERNETES' => 'myproject',
          'LAGOON_PROJECT' => 'myproject',
        ]),
        [
          'LAGOON_KUBERNETES' => 'myproject',
          'LAGOON_PROJECT' => 'myproject',
        ],
      ],

      [
        fn(): null => static::envSetMultiple([
          'LAGOON_KUBERNETES' => 'myproject',
          'LAGOON_PROJECT' => 'myproject',
          'OTHER_VAR' => 'value',
        ]),
        [
          'LAGOON_KUBERNETES' => 'myproject',
          'LAGOON_PROJECT' => 'myproject',
        ],
      ],
    ];
  }

  public static function dataProviderType(): array {
    // @formatter:off
    // phpcs:disable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
    // phpcs:disable Drupal.WhiteSpace.Comma.TooManySpaces
    // phpcs:disable Drupal.Files.LineLength.TooLong
    $matrix = [
      // LAGOON_ENVIRONMENT_TYPE, ENVIRONMENT_PRODUCTION_BRANCH, LAGOON_GIT_BRANCH.
      ['development', 'master',  'master',      Environment::PRODUCTION],
      ['development', 'master',  'main',        Environment::STAGE],
      ['development', 'master',  'release/123', Environment::STAGE],
      ['development', 'master',  'release',     Environment::DEVELOPMENT],
      ['development', 'master',  'hotfix/123',  Environment::STAGE],
      ['development', 'master',  'hotfix',      Environment::DEVELOPMENT],
      ['development', 'master',  'unset',       Environment::DEVELOPMENT],
      ['development', 'main',    'master',      Environment::STAGE],
      ['development', 'main',    'main',        Environment::PRODUCTION],
      ['development', 'main',    'release/123', Environment::STAGE],
      ['development', 'main',    'release',     Environment::DEVELOPMENT],
      ['development', 'main',    'hotfix/123',  Environment::STAGE],
      ['development', 'main',    'hotfix',      Environment::DEVELOPMENT],
      ['development', 'main',    'unset',       Environment::DEVELOPMENT],
      ['development', 'develop', 'master',      Environment::STAGE],
      ['development', 'develop', 'main',        Environment::STAGE],
      ['development', 'develop', 'release/123', Environment::STAGE],
      ['development', 'develop', 'release',     Environment::DEVELOPMENT],
      ['development', 'develop', 'hotfix/123',  Environment::STAGE],
      ['development', 'develop', 'hotfix',      Environment::DEVELOPMENT],
      ['development', 'develop', 'unset',       Environment::DEVELOPMENT],
      ['development', 'unset',   'master',      Environment::STAGE],
      ['development', 'unset',   'main',        Environment::STAGE],
      ['development', 'unset',   'release/123', Environment::STAGE],
      ['development', 'unset',   'release',     Environment::DEVELOPMENT],
      ['development', 'unset',   'hotfix/123',  Environment::STAGE],
      ['development', 'unset',   'hotfix',      Environment::DEVELOPMENT],
      ['development', 'unset',   'unset',       Environment::DEVELOPMENT],
      ['production',  'master',  'master',      Environment::PRODUCTION],
      ['production',  'master',  'main',        Environment::PRODUCTION],
      ['production',  'master',  'release/123', Environment::PRODUCTION],
      ['production',  'master',  'release',     Environment::PRODUCTION],
      ['production',  'master',  'hotfix/123',  Environment::PRODUCTION],
      ['production',  'master',  'hotfix',      Environment::PRODUCTION],
      ['production',  'master',  'unset',       Environment::PRODUCTION],
      ['production',  'main',    'master',      Environment::PRODUCTION],
      ['production',  'main',    'main',        Environment::PRODUCTION],
      ['production',  'main',    'release/123', Environment::PRODUCTION],
      ['production',  'main',    'release',     Environment::PRODUCTION],
      ['production',  'main',    'hotfix/123',  Environment::PRODUCTION],
      ['production',  'main',    'hotfix',      Environment::PRODUCTION],
      ['production',  'main',    'unset',       Environment::PRODUCTION],
      ['production',  'develop', 'master',      Environment::PRODUCTION],
      ['production',  'develop', 'main',        Environment::PRODUCTION],
      ['production',  'develop', 'release/123', Environment::PRODUCTION],
      ['production',  'develop', 'release',     Environment::PRODUCTION],
      ['production',  'develop', 'hotfix/123',  Environment::PRODUCTION],
      ['production',  'develop', 'hotfix',      Environment::PRODUCTION],
      ['production',  'develop', 'unset',       Environment::PRODUCTION],
      ['production',  'unset',   'master',      Environment::PRODUCTION],
      ['production',  'unset',   'main',        Environment::PRODUCTION],
      ['production',  'unset',   'release/123', Environment::PRODUCTION],
      ['production',  'unset',   'release',     Environment::PRODUCTION],
      ['production',  'unset',   'hotfix/123',  Environment::PRODUCTION],
      ['production',  'unset',   'hotfix',      Environment::PRODUCTION],
      ['production',  'unset',   'unset',       Environment::PRODUCTION],
      ['unset',       'master',  'master',      NULL],
      ['unset',       'master',  'main',        NULL],
      ['unset',       'master',  'release/123', NULL],
      ['unset',       'master',  'release',     NULL],
      ['unset',       'master',  'hotfix/123',  NULL],
      ['unset',       'master',  'hotfix',      NULL],
      ['unset',       'master',  'unset',       NULL],
      ['unset',       'main',    'master',      NULL],
      ['unset',       'main',    'main',        NULL],
      ['unset',       'main',    'release/123', NULL],
      ['unset',       'main',    'release',     NULL],
      ['unset',       'main',    'hotfix/123',  NULL],
      ['unset',       'main',    'hotfix',      NULL],
      ['unset',       'main',    'unset',       NULL],
      ['unset',       'develop', 'master',      NULL],
      ['unset',       'develop', 'main',        NULL],
      ['unset',       'develop', 'release/123', NULL],
      ['unset',       'develop', 'release',     NULL],
      ['unset',       'develop', 'hotfix/123',  NULL],
      ['unset',       'develop', 'hotfix',      NULL],
      ['unset',       'develop', 'unset',       NULL],
      ['unset',       'unset',   'master',      NULL],
      ['unset',       'unset',   'main',        NULL],
      ['unset',       'unset',   'release/123', NULL],
      ['unset',       'unset',   'release',     NULL],
      ['unset',       'unset',   'hotfix/123',  NULL],
      ['unset',       'unset',   'hotfix',      NULL],
      ['unset',       'unset',   'unset',       NULL],
    ];
    // @formatter:on
    // phpcs:enable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
    // phpcs:enable Drupal.WhiteSpace.Comma.TooManySpaces
    // phpcs:enable Drupal.Files.LineLength.TooLong

    $data = [];

    foreach ($matrix as $item) {
      $data[implode(', ', $item)] = [
        function () use ($item): void {
          static::envSet('LAGOON_KUBERNETES', 'myproject');
          if ($item[0] !== 'unset') {
            static::envSet('LAGOON_ENVIRONMENT_TYPE', $item[0]);
          }
          if ($item[1] !== 'unset') {
            static::envSet('ENVIRONMENT_PRODUCTION_BRANCH', $item[1]);
          }
          if ($item[2] !== 'unset') {
            static::envSet('LAGOON_GIT_BRANCH', $item[2]);
          }
        },
        $item[3],
      ];
    }

    return $data;
  }

  #[DataProvider('dataProviderApplyContextDrupal')]
  public function testApplyContextDrupal(callable $before, array $data, array $expected, ?callable $after = NULL): void {
    $before();

    static::envSet('LAGOON_KUBERNETES', 'myproject');
    Environment::applyContext($data);
    $this->assertEquals($expected, $data);

    if ($after !== NULL) {
      $after($this);
    }
  }

  public static function dataProviderApplyContextDrupal(): array {
    $default = ['settings' => ['hash_salt' => 'abc'], 'config' => []];

    return [
      [
        fn(): null => NULL,
        $default,
        array_merge_recursive([
          'settings' => [
            'reverse_proxy' => TRUE,
            'reverse_proxy_header' => 'HTTP_TRUE_CLIENT_IP',
            'trusted_host_patterns' => [
              '^nginx\-php$',
              '^.+\.au\.amazee\.io$',
            ],
          ],
          'config' => [],
        ], $default),
      ],

      [
        function (): void {
          static::envSet('LAGOON_ROUTES', 'http://example1.com,https://example2.com');
          static::envSet('LAGOON_PROJECT', 'myproject');
          static::envSet('LAGOON_GIT_SAFE_BRANCH', 'develop');
        },
        $default,
        array_merge_recursive([
          'settings' => [
            'reverse_proxy' => TRUE,
            'reverse_proxy_header' => 'HTTP_TRUE_CLIENT_IP',
            'cache_prefix' => 'myproject_develop',
            'trusted_host_patterns' => [
              '^nginx\-php$',
              '^.+\.au\.amazee\.io$',
              '^example1\.com|example2\.com$',
            ],
          ],
          'config' => [],
        ], $default),
      ],
      [
        function (): void {
          static::envSet('LAGOON_ROUTES', 'http://example1.com,https://example2/com');
          static::envSet('LAGOON_PROJECT', 'myproject');
          static::envSet('ENVIRONMENT_PRODUCTION_BRANCH', 'master');
        },
        $default,
        array_merge_recursive([
          'settings' => [
            'reverse_proxy' => TRUE,
            'reverse_proxy_header' => 'HTTP_TRUE_CLIENT_IP',
            'cache_prefix' => 'myproject_master',
            'trusted_host_patterns' => [
              '^nginx\-php$',
              '^.+\.au\.amazee\.io$',
              '^example1\.com|example2/com$',
            ],
          ],
          'config' => [],
        ], $default),
      ],
    ];
  }

}
