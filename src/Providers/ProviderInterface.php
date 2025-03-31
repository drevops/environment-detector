<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Providers;

use DrevOps\EnvironmentDetector\Contexts\ContextInterface;

/**
 * Provider interface.
 *
 * All providers should implement this interface.
 *
 * @package DrevOps\EnvironmentDetector\Providers
 */
interface ProviderInterface {

  /**
   * Get the provider ID.
   *
   * @return string
   *   The provider ID.
   */
  public function id(): string;

  /**
   * Get the provider label.
   *
   * @return string
   *   The provider label.
   */
  public function label(): string;

  /**
   * Check if the provider is active.
   *
   * @return bool
   *   TRUE if the provider is active, FALSE otherwise.
   */
  public function active(): bool;

  /**
   * Get the environment type.
   *
   * @return string|null
   *   The environment type or NULL if unable to resolve. Do not return the
   *   default environment type - this is decided outside the provider.
   */
  public function type(): ?string;

  /**
   * Get the provider data.
   *
   * @return array
   *   The provider data.
   */
  public function data(): array;

  /**
   * Apply the context.
   *
   * @param \DrevOps\EnvironmentDetector\Contexts\ContextInterface $context
   *   The context to apply.
   */
  public function contextualize(ContextInterface $context): void;

}
