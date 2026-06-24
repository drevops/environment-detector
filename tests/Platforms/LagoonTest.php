<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Platforms;

use DrevOps\EnvironmentDetector\Environment;
use DrevOps\EnvironmentDetector\Platforms\Lagoon;
use DrevOps\EnvironmentDetector\Tests\Fixtures\NotDrupalContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Lagoon::class)]
#[CoversClass(Environment::class)]
final class LagoonTest extends PlatformTestCase {

  public static function dataProviderActive(): \Iterator|array {
    return [
      yield [fn(): null => NULL, FALSE],
      yield [fn() => self::envSet('LAGOON_KUBERNETES', 'myproject'), TRUE],
      yield [fn() => self::envSet('LAGOON_PROJECT', 'myproject'), FALSE],
    ];
  }

  public static function dataProviderType(): array {
    // Each row is [LAGOON_ENVIRONMENT_TYPE, ENVIRONMENT_PRODUCTION_BRANCH,
    // LAGOON_GIT_BRANCH, expected type]. A value of 'unset' leaves that
    // variable unset. Under env-type 'development', the branch decides the
    // tier: the production branch is production; main/master (when not the
    // production branch), release/* and hotfix/* are stage; 'develop' is
    // development; anything else is an ephemeral preview.
    $matrix = [
      ['development', 'master', 'master', Environment::PRODUCTION],
      ['development', 'master', 'main', Environment::STAGE],
      ['development', 'master', 'develop', Environment::DEVELOPMENT],
      ['development', 'master', 'release/123', Environment::STAGE],
      ['development', 'master', 'release', Environment::PREVIEW],
      ['development', 'master', 'hotfix/123', Environment::STAGE],
      ['development', 'master', 'hotfix', Environment::PREVIEW],
      ['development', 'master', 'feature/foo', Environment::PREVIEW],
      ['development', 'master', 'unset', Environment::PREVIEW],
      ['development', 'main', 'master', Environment::STAGE],
      ['development', 'main', 'main', Environment::PRODUCTION],
      ['development', 'main', 'develop', Environment::DEVELOPMENT],
      ['development', 'main', 'release/123', Environment::STAGE],
      ['development', 'main', 'release', Environment::PREVIEW],
      ['development', 'main', 'hotfix/123', Environment::STAGE],
      ['development', 'main', 'hotfix', Environment::PREVIEW],
      ['development', 'main', 'feature/foo', Environment::PREVIEW],
      ['development', 'main', 'unset', Environment::PREVIEW],
      ['development', 'develop', 'master', Environment::STAGE],
      ['development', 'develop', 'main', Environment::STAGE],
      ['development', 'develop', 'develop', Environment::PRODUCTION],
      ['development', 'develop', 'release/123', Environment::STAGE],
      ['development', 'develop', 'release', Environment::PREVIEW],
      ['development', 'develop', 'hotfix/123', Environment::STAGE],
      ['development', 'develop', 'hotfix', Environment::PREVIEW],
      ['development', 'develop', 'feature/foo', Environment::PREVIEW],
      ['development', 'develop', 'unset', Environment::PREVIEW],
      ['development', 'unset', 'master', Environment::STAGE],
      ['development', 'unset', 'main', Environment::STAGE],
      ['development', 'unset', 'develop', Environment::DEVELOPMENT],
      ['development', 'unset', 'release/123', Environment::STAGE],
      ['development', 'unset', 'release', Environment::PREVIEW],
      ['development', 'unset', 'hotfix/123', Environment::STAGE],
      ['development', 'unset', 'hotfix', Environment::PREVIEW],
      ['development', 'unset', 'feature/foo', Environment::PREVIEW],
      ['development', 'unset', 'unset', Environment::PREVIEW],
      ['production', 'master', 'master', Environment::PRODUCTION],
      ['production', 'master', 'develop', Environment::PRODUCTION],
      ['production', 'master', 'feature/foo', Environment::PRODUCTION],
      ['production', 'master', 'unset', Environment::PRODUCTION],
      ['production', 'unset', 'develop', Environment::PRODUCTION],
      ['production', 'unset', 'unset', Environment::PRODUCTION],
      ['unset', 'master', 'master', NULL],
      ['unset', 'master', 'develop', NULL],
      ['unset', 'master', 'feature/foo', NULL],
      ['unset', 'master', 'unset', NULL],
      ['unset', 'unset', 'develop', NULL],
      ['unset', 'unset', 'unset', NULL],
      ['preview', 'unset', 'feature/foo', NULL],
    ];

    $data = [];

    foreach ($matrix as $item) {
      $data[implode(', ', $item)] = [
        function () use ($item): void {
          self::envSet('LAGOON_KUBERNETES', 'myproject');
          if ($item[0] !== 'unset') {
            self::envSet('LAGOON_ENVIRONMENT_TYPE', $item[0]);
          }
          if ($item[1] !== 'unset') {
            self::envSet('ENVIRONMENT_PRODUCTION_BRANCH', $item[1]);
          }
          if ($item[2] !== 'unset') {
            self::envSet('LAGOON_GIT_BRANCH', $item[2]);
          }
        },
        $item[3],
      ];
    }

    return $data;
  }

  public function testContextualizeNonDrupalIsNoop(): void {
    self::envSet('LAGOON_KUBERNETES', 'myproject');
    self::envSet('LAGOON_PROJECT', 'myproject');
    self::envSet('LAGOON_GIT_SAFE_BRANCH', 'develop');

    global $settings;
    $settings = [];

    // A non-Drupal context must not trigger any Drupal settings injection.
    (new Lagoon())->contextualize(new NotDrupalContext());

    $this->assertSame([], $settings);
  }

  #[DataProvider('dataProviderContextualizeDrupal')]
  public function testContextualizeDrupal(callable $before, array $expected, ?callable $after = NULL): void {
    $before();

    self::envSet('LAGOON_KUBERNETES', 'myproject');
    Environment::init();

    global $settings;
    global $config;

    $this->assertEquals($expected['settings'], $settings);
    $this->assertEquals($expected['config'], $config);

    if ($after !== NULL) {
      $after($this);
    }
  }

  public static function dataProviderContextualizeDrupal(): array {
    $default_settings = [
      'environment' => Environment::DEVELOPMENT,
      'hash_salt' => 'abc',
    ];
    $default_config = [];

    return [
      [
        function () use ($default_settings, $default_config): void {
          global $settings;
          global $config;
          $settings = $default_settings;
          $config = $default_config;
        },
        [
          'settings' => array_merge_recursive(
            [
              'reverse_proxy' => TRUE,
              'reverse_proxy_header' => 'HTTP_TRUE_CLIENT_IP',
              'trusted_host_patterns' => [
                '^nginx\-php$',
                '^.+\.au\.amazee\.io$',
              ],
            ] + $default_settings),
          'config' => array_merge_recursive([], $default_config),
        ],
      ],

      [
        function () use ($default_settings, $default_config): void {
          global $settings;
          global $config;
          $settings = $default_settings;
          $config = $default_config;
          self::envSet('LAGOON_ROUTES', 'http://example1.com,https://example2.com');
          self::envSet('LAGOON_PROJECT', 'myproject');
          self::envSet('LAGOON_GIT_SAFE_BRANCH', 'develop');
        },
        [
          'settings' => array_merge_recursive([
            'reverse_proxy' => TRUE,
            'reverse_proxy_header' => 'HTTP_TRUE_CLIENT_IP',
            'cache_prefix' => 'myproject_develop',
            'trusted_host_patterns' => [
              '^nginx\-php$',
              '^.+\.au\.amazee\.io$',
              '^(example1\.com|example2\.com)$',
            ],
          ], $default_settings),
          'config' => array_merge_recursive([], $default_config),
        ],
      ],
      [
        function () use ($default_settings, $default_config): void {
          global $settings;
          global $config;
          $settings = $default_settings;
          $config = $default_config;
          self::envSet('LAGOON_ROUTES', 'http://example1.com,https://example2/com');
          self::envSet('LAGOON_PROJECT', 'myproject');
          self::envSet('ENVIRONMENT_PRODUCTION_BRANCH', 'master');
        },
        [
          'settings' => array_merge_recursive([
            'reverse_proxy' => TRUE,
            'reverse_proxy_header' => 'HTTP_TRUE_CLIENT_IP',
            'cache_prefix' => 'myproject_master',
            'trusted_host_patterns' => [
              '^nginx\-php$',
              '^.+\.au\.amazee\.io$',
              '^(example1\.com|example2/com)$',
            ],
          ], $default_settings
          ),
          'config' => array_merge_recursive([], $default_config),
        ],
      ],
      [
        function () use ($default_settings, $default_config): void {
          global $settings;
          global $config;
          $settings = $default_settings;
          $config = $default_config;
          self::envSet('LAGOON_AMAZEEIO_REGION', 'us');
        },
        [
          'settings' => array_merge_recursive([
            'reverse_proxy' => TRUE,
            'reverse_proxy_header' => 'HTTP_TRUE_CLIENT_IP',
            'trusted_host_patterns' => [
              '^nginx\-php$',
              '^.+\.us\.amazee\.io$',
            ],
          ] + $default_settings),
          'config' => array_merge_recursive([], $default_config),
        ],
      ],
      [
        function () use ($default_settings, $default_config): void {
          global $settings;
          global $config;
          $settings = $default_settings;
          $config = $default_config;
          self::envSet('LAGOON_AMAZEEIO_REGION', '');
        },
        [
          'settings' => array_merge_recursive([
            'reverse_proxy' => TRUE,
            'reverse_proxy_header' => 'HTTP_TRUE_CLIENT_IP',
            'trusted_host_patterns' => [
              '^nginx\-php$',
            ],
          ] + $default_settings),
          'config' => array_merge_recursive([], $default_config),
        ],
      ],
    ];
  }

}
