<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Contexts;

/**
 * Context interface.
 *
 * Context is a framework/CMS that runs in the environment. Once the context is
 * detected, it can be used to conextualize (apply changes) to the running
 * environment. This can include setting environment variables, changing
 * configuration files, etc.
 *
 * @package DrevOps\EnvironmentDetector\Contexts
 */
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
   * Any required data _could_ be passed in as an argument, but better to use
   * the other means based on the implementation: environment variables,
   * configuration files, global variables, etc.
   *
   * @return bool
   *   TRUE if the context is active, FALSE otherwise.
   */
  public function active(): bool;

  /**
   * Apply the context.
   *
   * Any data that needs to be modified _could_ be passed in as an argument,
   * but better to use the other means based on the implementation: environment
   * variables, configuration files, global variables, etc.
   */
  public function contextualize(): void;

}
