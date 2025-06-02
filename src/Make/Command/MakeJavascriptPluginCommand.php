<?php declare(strict_types=1);

namespace Shopware\Development\Make\Command;

use RuntimeException;
use Shopware\Development\Make\Service\BundleFinder;
use Shopware\Development\Make\Service\JavascriptPluginGeneratorService;
use Shopware\Development\Make\Service\MainJsUpdaterService;
use Shopware\Development\Make\Service\NamespacePickerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'dev:make:plugin:js-plugin',
    description: 'Generates a JS plugin with Twig template'
)]
class MakeJavascriptPluginCommand extends Command
{
    public function __construct(
        private readonly BundleFinder                     $bundleFinder,
        private readonly NamespacePickerService           $namespacePickerService,
        private readonly JavascriptPluginGeneratorService $pluginGenerator,
        private readonly MainJsUpdaterService             $mainJsUpdater,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $pluginPath = $this->bundleFinder->askForBundle($io);

        $pluginConfig = [
            'pluginPath' => $pluginPath['path'],
            'pluginName' => $pluginPath['name'],
            'className' => $this->askForClassName($io),
        ];

        $pluginConfig['selector'] = $this->pluginGenerator->toKebabCase($pluginConfig['className']);

        $result = $this->pluginGenerator->generatePluginFiles(
            $pluginConfig['pluginPath'],
            $pluginConfig['className'],
            $pluginConfig['selector']
        );

        $this->showFileResults($io, $result);

        $updated = $this->mainJsUpdater->updateMainJs(
            $result['paths']['main_js'],
            $pluginConfig['selector'],
            $pluginConfig['className']
        );

        if ($updated) {
            $io->info("Updated main.js " . $result['paths']['main_js']);
        } else {
            $io->note("Plugin already registered in main.js");
        }

        return Command::SUCCESS;
    }

    private function askForClassName(SymfonyStyle $io): string
    {
        return $io->ask(
            'Please enter the JavaScript Plugin Class Name (e.g. "SuperPlugin"):',
            'MyPlugin',
            function ($answer) {
                if (empty($answer)) {
                    throw new RuntimeException('Class name cannot be empty.');
                }

                $className = trim($answer);

                return $className;
            }
        );
    }

    private function showFileResults(SymfonyStyle $io, array $result): void
    {
        if (!empty($result['created'])) {
            foreach ($result['created'] as $file) {
                $io->success("File created: $file");
            }
        }

        foreach ($result['paths'] as $type => $path) {
            if ($type !== 'main_js' && !in_array($path, $result['created'])) {
                $io->warning("File already exists (skipped): $path");
            }
        }
    }
}
