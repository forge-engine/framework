<?php

declare(strict_types=1);

namespace Forge\Core\Services;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

final class ArchiveService
{
    public function createZip(string $sourceDir, string $zipFilePath): bool
    {
        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        $sourceDir = rtrim($sourceDir, '/');

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($sourceDir) + 1);

                $zip->addFile($filePath, $relativePath);
            }
        }

        return $zip->close();
    }

    /**
     * Extract a ZIP archive to a destination directory, guarding against
     * zip-slip (entries escaping the target via ../) and enforcing a maximum
     * total uncompressed size. Returns the number of extracted files.
     */
    public function extractZip(string $zipPath, string $destinationPath, int $maxBytes = 0): int
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Failed to open ZIP archive.');
        }

        if (!is_dir($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        $destinationPath = rtrim($destinationPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $extracted = 0;
        $total = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            $stat = $zip->statIndex($i);

            if ($stat !== false && isset($stat['size'])) {
                $total += (int) $stat['size'];
                if ($maxBytes > 0 && $total > $maxBytes) {
                    $zip->close();
                    throw new \RuntimeException('ZIP contents exceed the allowed size.');
                }
            }

            // Zip-slip guard: strip leading slashes/dots, reject ../ traversal.
            // Directory entries end with a slash (empty last part) and ./ is a
            // harmless prefix, so only a literal ".." part is a traversal risk.
            $clean = ltrim(str_replace('\\', '/', $name), '/');
            $clean = preg_replace('#^\./+#', '', $clean) ?? $clean;
            $parts = explode('/', $clean);

            foreach ($parts as $part) {
                if ($part === '..') {
                    $zip->close();
                    throw new \RuntimeException('ZIP entry with unsafe path: ' . $name);
                }
            }

            $target = $destinationPath . $clean;

            if (str_ends_with($name, '/')) {
                if (!is_dir($target)) {
                    mkdir($target, 0755, true);
                }
                continue;
            }

            $dir = dirname($target);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $stream = $zip->getStream($name);
            if ($stream === false) {
                $zip->close();
                throw new \RuntimeException('Failed to read ZIP entry: ' . $name);
            }

            file_put_contents($target, stream_get_contents($stream));
            fclose($stream);
            $extracted++;
        }

        $zip->close();

        return $extracted;
    }

    public function calculateIntegrity(string $filePath): string|bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        return hash_file('sha256', $filePath);
    }
}
