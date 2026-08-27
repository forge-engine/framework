<?php
declare(strict_types=1);

namespace Forge\Core\Bootstrap;

use Forge\Core\DI\Container;
use Forge\Core\Config\Environment;
use Forge\Core\Helpers\Logger;
use Forge\Core\Session\Drivers\FileSessionDriver;
use Forge\Core\Session\Drivers\MemorySessionDriver;
use Forge\Core\Session\Drivers\SqliteSessionDriver;
use Forge\Core\Session\Session;
use Forge\Core\Session\SessionDriverInterface;
use Forge\Core\Session\SessionInterface;

final class SessionSetup
{
    public static function setup(Container $container): void
    {
        $env = Environment::getInstance();

        $container->singleton(SessionDriverInterface::class, function () use ($env) {
            $driverName = strtolower(trim($env->get('SESSION_DRIVER', 'file')));

            try {
                return match ($driverName) {
                    'memory' => new MemorySessionDriver(),
                    'sqlite' => new SqliteSessionDriver(),
                    'database' => new FileSessionDriver(),
                    'file' => new FileSessionDriver(),
                    default => new SqliteSessionDriver(),
                };
            } catch (\Throwable $e) {
                Logger::log('Failed to initialize session driver "' . $driverName . '"', $e->getMessage());

                return new FileSessionDriver();
            }
        });

        $container->singleton(SessionInterface::class, function () use ($container) {
            return new Session($container->make(SessionDriverInterface::class));
        });
    }
}
