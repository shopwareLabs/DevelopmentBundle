<?php

namespace Shopware\Development\Make\Service;

use Shopware\Core\Kernel;
use Symfony\Component\Console\Style\SymfonyStyle;

class BundleFinder
{
    private const BUILTIN_BUNDLES = [
        'Framework',
        'Core',
        'Storefront',
        'DbalKernelPluginLoader',
        'WebProfilerBundle',
        'Service',
        'Elasticsearch',
        'PentatrionViteBundle',
        'Administration',
        'Maintenance',
        'DevOps',
        'Checkout',
        'Content',
        'System',
        'DebugBundle',
        'TwigBundle',
        'MonologBundle',
        'Profiling',
        'FrameworkBundle',
    ];

    public function __construct(private readonly Kernel $kernel)
    {
    }

    /**
     * @return array<string, array{name: string, path: string, namespace: string}>
     */
    public function getAllBundles(): array
    {
        $list = [];

        foreach ($this->kernel->getBundles() as $bundle) {
            if (in_array($bundle->getName(), self::BUILTIN_BUNDLES, true)) {
                continue;
            }

            $list[$bundle->getName()] = [
                'name' => $bundle->getName(),
                'path' => $bundle->getPath(),
                'namespace' => $bundle->getNamespace(),
            ];
        }

        return $list;
    }

    public function getBundleByName(string $name): ?array
    {
        $bundles = $this->getAllBundles();

        return $bundles[$name] ?? null;
    }

    /**
     * Asks the user to select a bundle from the available bundles.
     *
     * @return array{name: string, path: string, namespace: string}
     */
    public function askForBundle(SymfonyStyle $io): array
    {
        $bundles = $this->getAllBundles();

        if (empty($bundles)) {
            $io->error('No custom bundles found.');
            throw new \RuntimeException('No custom bundles found.');
        }

        $choosen = $io->choice(
            'Select a bundle',
            array_keys($this->getAllBundles()),
            null
        );

        if ($choosen === null) {
            $io->error('No bundle selected.');
            throw new \RuntimeException('No bundle selected.');
        }

        return $bundles[$choosen];
    }
}
