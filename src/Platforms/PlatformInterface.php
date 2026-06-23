<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Platforms;

use DrevOps\EnvironmentDetector\Contexts\ContextInterface;

/**
 * Platform interface.
 *
 * A platform is the outermost ring of a running environment - the hosting
 * provider (Acquia, Lagoon, ...) or the CI service (GitHub Actions, ...). It is
 * the only ring that decides the environment type. At most one platform can be
 * active at a time.
 *
 * @package DrevOps\EnvironmentDetector\Platforms
 */
interface PlatformInterface {

  /**
   * Get the platform ID.
   *
   * @return string
   *   The platform ID.
   */
  public function id(): string;

  /**
   * Check if the platform is active.
   *
   * @return bool
   *   TRUE if the platform is active, FALSE otherwise.
   */
  public function active(): bool;

  /**
   * Get the environment type.
   *
   * @return string|null
   *   The environment type or NULL if unable to resolve. Do not return the
   *   default environment type - this is decided outside the platform.
   */
  public function type(): ?string;

  /**
   * Apply the context.
   *
   * @param \DrevOps\EnvironmentDetector\Contexts\ContextInterface $context
   *   The context to apply.
   */
  public function contextualize(ContextInterface $context): void;

}
