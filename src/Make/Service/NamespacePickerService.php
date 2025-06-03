<?php

declare(strict_types=1);

namespace Shopware\Development\Make\Service;

use RuntimeException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Path;

class NamespacePickerService
{
    const NAMESPACE_SRC = 'src/';
    const NAMESPACE_ADMINISTRATION_JS = 'Resources/app/administration/src/';
    const NAMESPACE_STOREFRONT_JS = 'Resources/app/storefront/src/';

    const DEFAULT_SRC = 'Service/MyService';
    const DEFAULT_ADMINISTRATION_JS = 'module/my-module';
    const DEFAULT_STOREFRONT_JS = 'plugin/my-plugin';

    public function pickNamespace(SymfonyStyle $io, array $pluginPath, string $default = ''): array
    {
        $serviceNamespace = $this->askForNamespace(
            $io,
            sprintf('Please enter the namespace path starting from %s (e.g. %s)',
                self::NAMESPACE_SRC,
                self::DEFAULT_SRC
            ),
            $default ?: self::DEFAULT_SRC
        );

        $pluginPath['path'] = Path::join($pluginPath['path'], $serviceNamespace);
        $pluginPath['namespace'] = $pluginPath['namespace'] . '\\' . str_replace('/', '\\', $serviceNamespace);

        return $pluginPath;
    }

    public function pickAdminJsNamespace(SymfonyStyle $io, array $pluginPath, string $default = ''): array
    {
        $serviceNamespace = $this->askForNamespace(
            $io,
            sprintf('Please enter the namespace path starting from %s (e.g. %s)',
                self::NAMESPACE_ADMINISTRATION_JS,
                self::DEFAULT_ADMINISTRATION_JS
            ),
            $default ?: self::DEFAULT_ADMINISTRATION_JS
        );

        $pluginPath['path'] = Path::join($pluginPath['path'], self::NAMESPACE_ADMINISTRATION_JS . $serviceNamespace);

        return $pluginPath;
    }

    public function pickStorefrontJsNamespace(SymfonyStyle $io, array $pluginPath, string $default = ''): array
    {
        $serviceNamespace = $this->askForNamespace(
            $io,
            sprintf('Please enter the namespace path starting from %s (e.g. %s)',
                self::NAMESPACE_STOREFRONT_JS,
                self::DEFAULT_STOREFRONT_JS
            ),
            $default ?: self::DEFAULT_STOREFRONT_JS
        );

        $pluginPath['path'] = Path::join($pluginPath['path'], self::NAMESPACE_STOREFRONT_JS . $serviceNamespace);

        return $pluginPath;
    }

    private function askForNamespace(SymfonyStyle $io, string $question, string $default): string
    {
        return $io->ask($question, $default, function ($answer) {
            if (empty($answer)) {
                throw new RuntimeException('The namespace path cannot be empty.');
            }

            return $answer;
        });
    }
}
