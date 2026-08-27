<?php

declare(strict_types=1);

namespace Forge\Traits;

use RuntimeException;

trait ModuleHelper
{
    private function checkModuleRequirements(string $moduleName): void
    {
        if (!isset($this->moduleRequirements[$moduleName])) {
            return;
        }

        $requirements = $this->moduleRequirements[$moduleName];

        foreach ($requirements['interfaces'] ?? [] as $interface => $version) {
            if (!$this->container->has($interface)) {
                throw new RuntimeException(
                    "Module '{$moduleName}' requires service '{$interface}' (version {$version}) which is not provided."
                );
            }
        }

        foreach ($requirements['modules'] ?? [] as $requiredModule => $versionConstraint) {
            $moduleDirName = $this->nameToPascalCase($requiredModule);
            // Match directories case-insensitively: normalized names like
            // "ForgeDatabaseSql" must resolve to "ForgeDatabaseSQL" on the
            // case-sensitive filesystems found on Linux servers.
            if (!$this->moduleRootExists($moduleDirName)) {
                throw new RuntimeException(
                    "Module '{$moduleName}' requires module '{$requiredModule}' (constraint: {$versionConstraint}) which is not installed."
                );
            }
        }

        unset($this->moduleRequirements[$moduleName]);
    }

    private function nameToPascalCase(string $name): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $name)));
    }

    private function moduleRootExists(string $moduleDirName): bool
    {
        foreach (\Forge\Core\Structure\StructureResolver::resolveModulesRoots() as $root) {
            $rootDir = BASE_PATH . '/' . $root;
            if (!is_dir($rootDir)) {
                continue;
            }

            foreach (scandir($rootDir) as $entry) {
                if (is_dir($rootDir . '/' . $entry) && strtolower($entry) === strtolower($moduleDirName)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function normalizeViewPath(string $name): string
    {
        $parts = preg_split('#[/:]#', $name);
        array_shift($parts);
        $parts = array_map(fn($p) => strtolower($p), $parts);
        return implode('/', $parts);
    }
}
