<?php

declare(strict_types=1);

namespace Shopware\Development\Make\Service;

use RuntimeException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

class NamespacePickerService
{
    public function pickNamespace(SymfonyStyle $io, string $pluginPath): array
    {
        $serviceNamespace = $io->ask('Please enter the namespace path starting from src (e.g. Service/MyService)', null, function ($answer) {
            if (empty($answer)) {
                throw new RuntimeException('The namespace path cannot be empty.');
            }

            return $answer;
        });

        $bundleNamespace =  $this->detectNamespaceFromMainClass($pluginPath . '/src/');
        if (empty($bundleNamespace)) {
            $io->error('Could not detect the main plugin class.');
            throw new RuntimeException('Main plugin class namespace could not be detected.');
        }

        $namespace = $bundleNamespace . '\\' . str_replace('/', '\\', $serviceNamespace);

        return [
            'fullPath' => $pluginPath . '/src/' . $serviceNamespace,
            'namespace' => $namespace,
        ];
    }

    private function detectNamespaceFromMainClass(string $srcPath): ?string
    {
        $finder = new Finder();
        $finder->files()
            ->in($srcPath)
            ->name('*.php')
            ->depth(0);

        foreach ($finder as $file) {
            $content = $file->getContents();

            if (preg_match('/^namespace\s+([^;]+);/m', $content, $matches)) {
                $namespace = trim($matches[1]);

                if ($this->isMainPluginClass($content)) {
                    return $namespace;
                }
            }
        }

        return null;
    }

    private function isMainPluginClass(string $content): bool
    {
        $patterns = [
            '/class\s+\w+\s+extends\s+Plugin/',
            '/class\s+\w+Bundle\s+extends\s+Bundle/',
            '/class\s+\w+Plugin\s+extends/',
            '/implements\s+.*PluginInterface/',
            '/extends\s+.*PluginBootstrap/',
            '/extends\s+.*BasePlugin/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }
}
