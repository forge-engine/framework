<?php

declare(strict_types=1);

namespace Forge\Core\Contracts;

use Throwable;

/**
 * Kernel-owned contract for any service that provides application error
 * handling. A module self-registers an implementation (via its `register()`
 * binding and/or the #[Provides] attribute) and the kernel discovers it
 * generically — no capability namespace is assumed.
 *
 * `$context` is an optional, framework-specific request object the caller may
 * pass (e.g. ForgeRouter's request). Handlers that need request context can
 * type-check it; handlers that don't may ignore it.
 *
 * @return mixed A renderable result (e.g. a router Response), or null/void.
 */
interface ErrorHandlerInterface
{
    public function handle(Throwable $e, ?object $context = null): mixed;
}
