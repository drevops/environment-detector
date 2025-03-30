<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Contexts;

interface ContextInterface {

  /**
   * Get the context ID.
   *
   * @return string
   *   The context ID.
   */
  public function id(): string;

  /**
   * Get the context label.
   *
   * @return string
   *   The context label.
   */
  public function label(): string;

  /**
   * Check if the context is active.
   *
   * @return bool
   *   TRUE if the context is active, FALSE otherwise.
   */
  public function active(?array $data = NULL): bool;

}
