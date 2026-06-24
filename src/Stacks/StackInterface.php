<?php

declare(strict_types=1);

namespace DrevOps\EnvironmentDetector\Stacks;

use DrevOps\EnvironmentDetector\Contexts\ContextInterface;

/**
 * Stack interface.
 *
 * A stack is the substrate an environment runs in (the native host, a
 * container, or a more specific container). It nests inside a platform and
 * never decides the environment type, so it has no type(). Exactly one stack is
 * always active - the most specific one that matches, or the native host as the
 * last-resort fallback. The active stack may contribute settings.
 *
 * @package DrevOps\EnvironmentDetector\Stacks
 */
interface StackInterface {

  /**
   * Get the stack ID.
   *
   * @return string
   *   The stack ID.
   */
  public function id(): string;

  /**
   * Check if the stack is active.
   *
   * @return bool
   *   TRUE if the stack is active, FALSE otherwise.
   */
  public function active(): bool;

  /**
   * Apply the context.
   *
   * @param \DrevOps\EnvironmentDetector\Contexts\ContextInterface $context
   *   The context to apply.
   */
  public function contextualize(ContextInterface $context): void;

}
