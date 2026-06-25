<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Contexts;

/**
 * Context interface.
 *
 * Context is a framework/CMS that runs in the environment. Once the context is
 * detected, it can be used to contextualize (apply changes) to the running
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
   * Check if the context is active.
   *
   * The method takes no arguments; an implementation reads whatever it needs
   * from the state it holds (for example values injected at construction),
   * environment variables, or configuration files.
   *
   * @return bool
   *   TRUE if the context is active, FALSE otherwise.
   */
  public function active(): bool;

  /**
   * Apply the context.
   *
   * The method takes no arguments; an implementation applies changes to the
   * state it holds (for example framework settings injected by reference at
   * construction, so the changes land in the arrays the framework reads back),
   * environment variables, or configuration files.
   */
  public function contextualize(): void;

}
