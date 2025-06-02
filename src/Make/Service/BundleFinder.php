<?php

namespace Shopware\Development\Make\Service;

use Shopware\Core\Kernel;

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
}
