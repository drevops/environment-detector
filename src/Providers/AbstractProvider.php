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
   *
   * @return array<string>
   *   The list of environment variable prefixes used by the provider.
   */
  abstract protected function envPrefixes(): array;

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
    return empty($this->envPrefixes())
        ? []
        : array_filter(getenv(), fn($key): bool => array_reduce(static::envPrefixes(), fn($carry, $prefix): bool => $carry || str_starts_with($key, $prefix), FALSE), ARRAY_FILTER_USE_KEY);
  }

  /**
   * {@inheritdoc}
   */
  public function contextualize(ContextInterface $context): void {
    $method = 'contextualize' . static::snakeToCamel($context->id());
    if (method_exists($this, $method)) {
      $this->$method();
    }
  }

  /**
   * Convert a snake_case string to camelCase.
   *
   * @param string $string
   *   The string to convert.
   *
   * @return string
   *   The converted string.
   */
  protected static function snakeToCamel(string $string): string {
    return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $string)));
  }

}
