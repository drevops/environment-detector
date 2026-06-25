<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests;

use DrevOps\EnvironmentDetector\Environment;
use PHPUnit\Framework\Attributes\CoversNothing;

#[CoversNothing]
final class EnvironmentDrupalEntryPointTest extends EnvironmentDetectorTestCase {

  public function testEntryPointActivatesDrupalContext(): void {
    // Pin the container stack so the applied settings are host-independent.
    self::envSet('DOCKER', 'TRUE');

    $settings = ['hash_salt' => 'abc'];
    $config = [];

    require dirname(__DIR__) . '/environment.drupal.php';

    // The entry point wires the site's $settings into an active Drupal context,
    // so detection writes back the type, the universal loopback set, and the
    // active stack's settings end to end.
    $this->assertSame('drupal', Environment::getActiveContext()?->id());
    $this->assertSame([
      'hash_salt' => 'abc',
      'environment' => Environment::LOCAL,
      'trusted_host_patterns' => [
        '^localhost$',
        '^127\.0\.0\.1$',
        '^(web|app|webserver|nginx|apache|apache2)$',
      ],
    ], $settings);
  }

}
