<?php declare(strict_types=1);

namespace Shopware\Development\Make\Service;

use Symfony\Component\Filesystem\Filesystem;

class MainJsUpdaterService
{
    public function __construct(
        private readonly Filesystem $filesystem = new Filesystem()
    ) {
    }

    public function updateMainJs(string $mainJsPath, string $selector, string $className): bool
    {
        if (!file_exists($mainJsPath)) {
            $content = "// Auto-generated main.js\n";
        } else {
            $content = file_get_contents($mainJsPath);
        }

        $importStatement = "import {$className} from './{$selector}/{$selector}';";
        $registerStatement = "PluginManager.register('{$className}', {$className}, '[data-{$selector}]');";

        $updated = false;

        if (!$this->containsImport($content, $importStatement)) {
            $content = $this->addImport($content, $importStatement);
            $updated = true;
        }

        if (!$this->hasPluginManagerDeclaration($content)) {
            $content = $this->addPluginManagerDeclaration($content);
            $updated = true;
        }

        if (!$this->containsRegistration($content, $registerStatement)) {
            $content .= "\n" . $registerStatement;
            $updated = true;
        }

        if ($updated) {
            $this->filesystem->mkdir(dirname($mainJsPath), 0755, true);
            file_put_contents($mainJsPath, $content);
        }

        return $updated;
    }

    private function containsImport(string $content, string $importStatement): bool
    {
        return strpos($content, $importStatement) !== false;
    }

    private function containsRegistration(string $content, string $registerStatement): bool
    {
        return strpos($content, $registerStatement) !== false;
    }

    private function hasPluginManagerDeclaration(string $content): bool
    {
        return strpos($content, 'const PluginManager = window.PluginManager;') !== false;
    }

    private function hasPluginManagerUsage(string $content): bool
    {
        return strpos($content, 'PluginManager.register') !== false;
    }

    private function addImport(string $content, string $importStatement): string
    {
        $lines = explode("\n", $content);
        $lastImportIndex = $this->findLastImportIndex($lines);

        if ($lastImportIndex >= 0) {
            array_splice($lines, $lastImportIndex + 1, 0, $importStatement);
        } else {
            array_unshift($lines, $importStatement);
        }

        return implode("\n", $lines);
    }

    private function addPluginManagerDeclaration(string $content): string
    {
        $lines = explode("\n", $content);
        $insertIndex = $this->findImportEndIndex($lines);

        array_splice($lines, $insertIndex, 0, "\nconst PluginManager = window.PluginManager;");
        return implode("\n", $lines);
    }

    private function findLastImportIndex(array $lines): int
    {
        $lastImportIndex = -1;
        foreach ($lines as $index => $line) {
            $trimmedLine = trim($line);
            if (strpos($trimmedLine, 'import ') === 0 && strpos($trimmedLine, ' from ') !== false) {
                $lastImportIndex = $index;
            }
        }
        return $lastImportIndex;
    }

    private function findImportEndIndex(array $lines): int
    {
        $insertIndex = 0;
        foreach ($lines as $index => $line) {
            $trimmedLine = trim($line);
            if (strpos($trimmedLine, 'import ') === 0) {
                $insertIndex = $index + 1;
            }
        }
        return $insertIndex;
    }
}
