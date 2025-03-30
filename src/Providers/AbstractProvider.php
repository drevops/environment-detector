<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Providers;

use DrevOps\EnvironmentDetector\Contexts\ContextInterface;

/**
 * Abstract provider.
 *
 * All providers should extend this class.
 *
 * @package DrevOps\EnvironmentDetector\Providers
 */
abstract class AbstractProvider implements ProviderInterface {

  /**
   * Provider ID. Providers should override this constant.
   */
  public const string ID = 'undefined';

  /**
   * Provider label. Providers should override this constant.
   */
  public const string LABEL = 'undefined';

  /**
   * Environment variables prefix. Providers should override this constant.
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

  public function applyContext(ContextInterface $context, ?array &$data = NULL): void {
    $method = 'applyContext' . static::snakeToCamel($context->id());

    if (method_exists($this, $method)) {
      $this->$method($data);
    }
  }

  protected static function snakeToCamel(string $string): string {
    return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $string)));
  }

}
