<?php

namespace Shopware\Development\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class DevelopmentBundleExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);
        $this->addConfig($container, $this->getAlias(), $config);
    }

    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        return new Configuration();
    }

    private function addConfig(ContainerBuilder $container, string $alias, array $options): void
    {
        foreach ($options as $key => $option) {
            $container->setParameter($alias . '.' . $key, $option);

            if (\is_array($option)) {
                $this->addConfig($container, $alias . '.' . $key, $option);
            }
        }
    }

    public function getAlias(): string
    {
        return 'shopware_development';
    }
}
