<?php declare(strict_types=1);

namespace Shopware\Development\Make\Command;

use RuntimeException;
use Shopware\Development\Make\Service\BundleFinderService;
use Shopware\Development\Make\Service\NamespacePickerService;
use Shopware\Development\Make\Service\TemplateService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'dev:make:plugin:js-plugin',
    description: 'Generates a JS plugin with Twig template'
)]
class MakeJavascriptPluginCommand extends AbstractMakeCommand
{
    private const MAX_MODULE_NAME_LENGTH = 50;

    public function __construct(
        BundleFinderService    $bundleFinder,
        NamespacePickerService $namespacePickerService,
        TemplateService        $templateService,
    )
    {
        parent::__construct($bundleFinder, $namespacePickerService, $templateService);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $pluginPath = $this->bundleFinder->askForBundle($io);

        $pluginConfig = [
            'pluginPath' => $pluginPath['path'],
            'className' => $this->askForClassName($io),
        ];

        $pluginConfig['selector'] = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $pluginConfig['className']));

        $result = $this->generatePluginFiles(
            $pluginConfig['pluginPath'],
            $pluginConfig['className'],
            $pluginConfig['selector']
        );

        $this->showFileResults($io, $result);

        $this->addSelectorToTwigTemplate($result['paths']['twig'], $pluginConfig['selector']);

        $updated = $this->updateMainJs(
            $result['paths']['main_js'],
            $pluginConfig['selector'],
            $pluginConfig['className']
        );

        $updated
            ? $io->info("Updated main.js " . $result['paths']['main_js'])
            : $io->note("Plugin already registered in main.js");

        return Command::SUCCESS;
    }

    private function askForClassName(SymfonyStyle $io): string
    {
        $className = $io->ask(
            'Please enter the JavaScript Plugin Class Name (e.g. "SuperPlugin"):',
            'MyPlugin',
            function ($answer) {
                $this->validateClassName($answer);
                return trim($answer);
            }
        );

        $io->success(sprintf('Class name set to: %s', $className));

        return $className;
    }

    private function validateClassName(string $name): void
    {
        $trimmed = trim($name);

        if (empty($trimmed)) {
            throw new RuntimeException('Module name cannot be empty.');
        }

        if (!preg_match('/^[A-Z][a-zA-Z0-9]*$/', $trimmed)) {
            throw new RuntimeException(
                'Module name must start with uppercase letter and contain only alphanumeric characters.'
            );
        }

        if (strlen($trimmed) > self::MAX_MODULE_NAME_LENGTH) {
            throw new RuntimeException(
                sprintf('Module name cannot be longer than %d characters.', self::MAX_MODULE_NAME_LENGTH)
            );
        }
    }

    private function showFileResults(SymfonyStyle $io, array $result): void
    {
        foreach ($result['created'] as $file) {
            $io->success("File created: $file");
        }

        foreach ($result['paths'] as $type => $path) {
            if ($type !== 'main_js' && !in_array($path, $result['created'])) {
                $io->warning("File already exists (skipped): $path");
            }
        }
    }

    private function generatePluginFiles(string $basePath, string $className, string $selector): array
    {
        $paths = [
            'js' => $basePath . "/Resources/app/storefront/src/{$selector}/{$selector}.js",
            'twig' => $basePath . '/Resources/views/storefront/page/content/index.html.twig',
            'main_js' => $basePath . '/Resources/app/storefront/src/main.js',
        ];

        $createdFiles = [];

        $this->writeFileIfNotExists('/javascript-plugin/js-plugin.js.template', [
            'className' => $className,
            'selector' => $selector,
        ], $paths['js'], $createdFiles);

        $this->writeFileIfNotExists('/javascript-plugin/js-plugin.twig.template', [
            'selector' => $selector,
        ], $paths['twig'], $createdFiles);

        return [
            'paths' => $paths,
            'created' => $createdFiles,
        ];
    }

    private function writeFileIfNotExists(string $templateName, array $vars, string $targetPath, array &$createdFiles): void
    {
        if (!file_exists($targetPath)) {
            $content = $this->templateService->generateTemplate($templateName, $vars);
            $this->templateService->createFile($content, $targetPath);
            $createdFiles[] = $targetPath;
        }
    }

    private function addSelectorToTwigTemplate(string $twigPath, string $selector): bool
    {
        $templateLine = "    <template data-{$selector}></template>";
        $fileExists = file_exists($twigPath);
        $content = $fileExists ? file_get_contents($twigPath) : '';

        if (!$fileExists || trim($content) === '') {
            $content = <<<TWIG
        {% sw_extends '@Storefront/storefront/page/content/index.html.twig' %}

        {% block base_main_inner %}
            {{ parent() }}
            $templateLine
        {% endblock %}
        TWIG;
            $this->templateService->createFile($content, $twigPath);
            return true;
        }

        if (str_contains($content, $templateLine)) {
            return false;
        }

        $pattern = '/{% block base_main_inner %}(.*?){% endblock %}/s';

        if (preg_match($pattern, $content, $matches)) {
            $inner = rtrim($matches[1]);

            $newInner = $inner . "\n" . $templateLine . "\n";
            $newBlock = "{% block base_main_inner %}" . $newInner . "{% endblock %}";

            $content = preg_replace($pattern, $newBlock, $content);
        } else {
            if (!str_contains($content, '{% sw_extends')) {
                $content = "{% sw_extends '@Storefront/storefront/page/content/index.html.twig' %}\n\n" . $content;
            }

            $block = <<<TWIG

{% block base_main_inner %}
    {{ parent() }}
    $templateLine
{% endblock %}
TWIG;

            $content = rtrim($content) . "\n" . $block . "\n";
        }

        $this->templateService->createFile($content, $twigPath);
        return true;
    }

    private function updateMainJs(string $mainJsPath, string $selector, string $className): bool
    {
        $importStatement = "import {$className} from './{$selector}/{$selector}';";
        $pluginManagerLine = 'const PluginManager = window.PluginManager;';
        $registerStatement = "PluginManager.register('{$className}', {$className}, '[data-{$selector}]');";

        $lines = file_exists($mainJsPath)
            ? file($mainJsPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
            : [];

        $updated = false;

        // Flags zum Tracking
        $hasImport = false;
        $hasPluginManager = false;
        $hasRegistration = false;

        // Positionen merken
        $lastImportIndex = -1;
        $pluginManagerIndex = -1;

        foreach ($lines as $i => $line) {
            $trimmed = trim($line);

            if ($trimmed === $importStatement) {
                $hasImport = true;
            }

            if (str_starts_with($trimmed, 'import ')) {
                $lastImportIndex = $i;
            }

            if ($trimmed === $pluginManagerLine) {
                $hasPluginManager = true;
                $pluginManagerIndex = $i;
            }

            if ($trimmed === $registerStatement) {
                $hasRegistration = true;
            }
        }

        // Import einfügen
        if (!$hasImport) {
            array_splice($lines, $lastImportIndex + 1, 0, $importStatement);
            $updated = true;
            $pluginManagerIndex++; // verschiebt sich
        }

        // PluginManager-Deklaration einfügen
        if (!$hasPluginManager) {
            array_splice($lines, $lastImportIndex + 2, 0, $pluginManagerLine);
            $pluginManagerIndex = $lastImportIndex + 2;
            $updated = true;
        }

        // Registrierung einfügen
        if (!$hasRegistration) {
            $insertIndex = $pluginManagerIndex !== -1 ? $pluginManagerIndex + 1 : count($lines);
            array_splice($lines, $insertIndex, 0, $registerStatement);
            $updated = true;
        }

        if ($updated) {
            $content = implode("\n", $lines) . "\n";
            $this->templateService->createFile($content, $mainJsPath);
        }

        return $updated;
    }


    private function findLastImportIndex(array $lines): int
    {
        $lastImportIndex = -1;
        foreach ($lines as $i => $line) {
            if (str_starts_with(trim($line), 'import ')) {
                $lastImportIndex = $i;
            }
        }
        return $lastImportIndex;
    }
}
