<?php

declare(strict_types=1);

namespace Shopware\Development\Make\Service;

use RuntimeException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Path;

class NamespacePickerService
{
    const ADMIN_NAMESPACE = 'Resources/app/administration/src/';

    public function pickNamespace(SymfonyStyle $io, array $pluginPath, string $default = ''): array
    {
        $serviceNamespace = $io->ask('Please enter the namespace path starting from src (e.g. Service/MyService)', $default, function ($answer) {
            if (empty($answer)) {
                throw new RuntimeException('The namespace path cannot be empty.');
            }

            return $answer;
        });

        $pluginPath['path'] = Path::join($pluginPath['path'], $serviceNamespace);
        $pluginPath['namespace'] = $pluginPath['namespace'] . '\\' . str_replace('/', '\\', $serviceNamespace);

        return $pluginPath;
    }

    public function pickAdminNamespace(SymfonyStyle $io, array $pluginPath, string $default = ''): array
    {
        $serviceNamespace = $io->ask('Please enter the namespace path starting from Resources/app/administration/src (e.g. module/my-module)', $default, function ($answer) {
            if (empty($answer)) {
                throw new RuntimeException('The namespace path cannot be empty.');
            }

            return $answer;
        });

        $pluginPath['path'] = Path::join($pluginPath['path'], self::ADMIN_NAMESPACE . $serviceNamespace);

        return $pluginPath;
    }
}
