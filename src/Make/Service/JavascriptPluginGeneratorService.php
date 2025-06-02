<?php declare(strict_types=1);

namespace Shopware\Development\Make\Service;

use Symfony\Component\Filesystem\Filesystem;

class JavascriptPluginGeneratorService
{
    public function __construct(
        private readonly Filesystem $filesystem = new Filesystem()
    ) {}

    public function generatePluginFiles(string $pluginBasePath, string $className, string $selector): array
    {
        $paths = $this->generatePaths($pluginBasePath, $selector);
        $createdFiles = [];

        if (!$this->filesystem->exists($paths['js'])) {
            $this->filesystem->mkdir(dirname($paths['js']), 0755, true);
            $this->filesystem->dumpFile($paths['js'], $this->generateJsPluginCode($className, $selector));
            $createdFiles[] = $paths['js'];
        }

        if (!$this->filesystem->exists($paths['twig'])) {
            $this->filesystem->mkdir(dirname($paths['twig']), 0755, true);
            $this->filesystem->dumpFile($paths['twig'], $this->generateTwigTemplate($selector));
            $createdFiles[] = $paths['twig'];
        }

        return [
            'paths' => $paths,
            'created' => $createdFiles
        ];
    }

    public function generatePaths(string $pluginBasePath, string $selector): array
    {
        return [
            'js' => $pluginBasePath . '/Resources/app/storefront/src/' . $selector . '/' . $selector . '.js',
            'twig' => $pluginBasePath . '/Resources/views/storefront/page/content/index.html.twig',
            'main_js' => $pluginBasePath . '/Resources/app/storefront/src/main.js',
        ];
    }

    public function generateJsPluginCode(string $className, string $selector): string
    {
        return <<<JS
import Plugin from 'src/plugin-system/plugin.class';

export default class {$className} extends Plugin {
    init() {
        console.log('{$className} initialized');
    }
}
JS;
    }

    public function generateTwigTemplate(string $selector): string
    {
        return <<<TWIG
{% sw_extends '@Storefront/storefront/page/content/index.html.twig' %}

{% block base_main_inner %}
    {{ parent() }}

    <template data-{$selector}></template>
{% endblock %}
TWIG;
    }

    public function toKebabCase(string $input): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $input));
    }
}
