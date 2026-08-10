<?php

declare(strict_types=1);

namespace Forge\Core\Contracts;

interface EventDispatcherInterface
{
    public function addListener(string $eventClass, array|callable $handler): void;
    public function dispatch(object $event): void;
}
