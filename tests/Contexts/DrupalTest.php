<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Tests\Contexts;

use DrevOps\EnvironmentDetector\Contexts\Drupal;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Drupal::class)]
class DrupalTest extends ContextTestBase {

  public static function dataProviderActive(): array {
    return [
      [fn(): null => NULL, FALSE],
      [fn(): array => [], FALSE],
      [fn(): array => ['settings' => [], 'config' => []], FALSE],
      [fn(): array => ['settings' => ['hash_salt' => 'abc'], 'config' => []], TRUE],
      [fn(): array => ['settings' => [], 'config' => ['system.site' => ['uuid' => '123']]], TRUE],
    ];
  }

}
