<?php

namespace Shopware\Development;

use Shopware\Core\Framework\Bundle;
use Shopware\Core\Kernel;
use Shopware\Development\DependencyInjection\DevelopmentBundleExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class DevelopmentBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $this->buildConfig($container);
    }


    protected function createContainerExtension(): ExtensionInterface
    {
        return new DevelopmentBundleExtension();
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new DevelopmentBundleExtension();
    }

    private function buildConfig(ContainerBuilder $container): void
    {
        $locator = new FileLocator('Resources/config');

        $resolver = new LoaderResolver([
            new YamlFileLoader($container, $locator),
            new GlobFileLoader($container, $locator),
        ]);

        $configLoader = new DelegatingLoader($resolver);

        $confDir = $this->getPath() . '/Resources/config';

        $configLoader->load($confDir . '/{packages}/*' . Kernel::CONFIG_EXTS, 'glob');
    }
}
