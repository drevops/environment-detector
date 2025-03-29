<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Providers;

/**
 * Abstract provider.
 *
 * All providers should extend this class.
 *
 * @package DrevOps\EnvironmentDetector\Providers
 */
abstract class AbstractProvider implements ProviderInterface {

  /**
   * Provider ID. Provers should override this constant.
   */
  public const string ID = 'undefined';

  /**
   * Provider label. Provers should override this constant.
   */
  public const string LABEL = 'undefined';

  /**
   * Environment variables prefix. Provers should override this constant.
   */
  abstract protected static function envPrefixes(): array;

  /**
   * {@inheritdoc}
   */
  public function id(): string {
    return static::ID;
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return static::LABEL;
  }

  /**
   * {@inheritdoc}
   */
  public function data(): array {
    return
      empty(static::envPrefixes())
        ? []
        : array_filter(getenv(), fn($key): bool => array_reduce(static::envPrefixes(), fn($carry, $prefix): bool => $carry || str_starts_with($key, $prefix), FALSE), ARRAY_FILTER_USE_KEY);
  }

}
