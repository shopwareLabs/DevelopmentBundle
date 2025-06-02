<?php

declare(strict_types=1);

namespace Shopware\Development\Make\Command;

use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'dev:make:plugin:admin-module',
    description: 'Generates a Vue admin module for a Shopware plugin'
)]
class MakeAdminModuleCommand extends AbstractMakeCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $pluginPath = $this->bundleFinder->askForBundle($io);
        $moduleConfig = $this->namespacePickerService->pickAdminNamespace($io, $pluginPath);

        $moduleConfig['name'] = $this->moduleName($io);
        $moduleConfig['parent'] = $this->moduleParent($io);
        $moduleConfig['color'] = $this->pickColor($io);

        $this->createAdminModule($moduleConfig, $io);

        return self::SUCCESS;
    }

    private function moduleName(SymfonyStyle $io): string
    {
        return $io->ask(
            'Please enter the name of the module (e.g. "MyCustomModule"):',
            'MyCustomModule',
            function ($answer) {
                if (empty($answer)) {
                    throw new RuntimeException('Module name cannot be empty.');
                }

                return trim($answer);
            }
        );
    }

    private function createAdminModule(array $moduleConfig, SymfonyStyle $io): void
    {
        $modulePath = $moduleConfig['path'];
        $moduleName = $moduleConfig['name'];
        $moduleParent = $moduleConfig['parent'];
        $moduleColor = $moduleConfig['color'];

        $this->fileSystem->mkdir($modulePath);

        $moduleFileName = $this->convertModuleName($moduleName) . '.js';
        $moduleFilePath = $modulePath . '/' . $moduleFileName;

        $moduleContent = $this->generateModuleContent($moduleName, $moduleParent, $moduleColor);

        $this->fileSystem->dumpFile($moduleFilePath, $moduleContent);

        $io->success(sprintf('Admin module created successfully at: %s', $moduleFilePath));
        $io->note([
            'Module details:',
            sprintf('- Name: %s', $moduleName),
            sprintf('- Parent: %s', $moduleParent),
            sprintf('- Color: %s', $moduleColor),
            sprintf('- File: %s', $moduleFileName),
        ]);
    }

    private function generateModuleContent(string $moduleName, string $parent, string $color): string
    {
        $moduleId = $this->convertModuleName($moduleName);
        $templatePath = __DIR__ . '/../Template/admin-module.js';

        $template = file_get_contents($templatePath);

        $replacements = [
            '{{MODULE_NAME}}' => $moduleName,
            '{{MODULE_ID}}' => $moduleId,
            '{{COLOR}}' => $color,
            '{{PARENT}}' => $parent,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    private function convertModuleName(string $input): string
    {
        $input = trim($input);
        $input = preg_replace('/[^a-zA-Z0-9\s]/', '', $input);
        $input = preg_replace('/\s+/', '-', $input);
        $input = preg_replace('/([a-z])([A-Z])/', '$1-$2', $input);
        return strtolower($input);
    }

    private function moduleParent(SymfonyStyle $io): string
    {
        $availableParents = [
            'sw-product' => 'Products',
            'sw-order' => 'Orders',
            'sw-customer' => 'Customers',
            'sw-cms' => 'Content Management',
            'sw-analytics' => 'Analytics & Reports',
            'sw-extension' => 'Extensions & Apps',
            'sw-settings' => 'Settings',
        ];

        $io->section('Select Parent Module');
        $io->text('Choose the parent module where your new module should be located:');
        $io->newLine();

        $choices = [];
        foreach ($availableParents as $key => $description) {
            $choices[] = sprintf('%s (%s)', $key, $description);
        }

        $selectedChoice = $io->choice(
            'Please select a parent module:',
            $choices,
            $choices[0]
        );

        preg_match('/^(\S+)/', $selectedChoice, $matches);
        $selectedParent = $matches[1];

        $io->success(sprintf('Selected parent module: %s', $selectedParent));

        return $selectedParent;
    }

    private function pickColor(SymfonyStyle $io): string
    {
        $shopwareColors = [
            '#189EFF' => 'Shopware Blue (Primary)',
            '#52667A' => 'Shopware Dark Blue',
            '#758CA3' => 'Shopware Light Blue',
            '#FF6900' => 'Shopware Orange',
            '#37D046' => 'Shopware Green',
            '#DE294C' => 'Shopware Red',
            '#FFB900' => 'Shopware Yellow',
            '#8B45B6' => 'Shopware Purple',
            '#00B8D4' => 'Shopware Cyan',
            '#795548' => 'Shopware Brown',
        ];

        $io->section('Select Module Color');
        $io->text('Choose a color for your module (this will be used for icons, highlights, etc.):');
        $io->newLine();

        $choices = ['Custom color (enter hex code)'];
        foreach ($shopwareColors as $hex => $description) {
            $choices[] = sprintf('%s - %s', $hex, $description);
        }

        $selectedChoice = $io->choice(
            'Please select a color:',
            $choices,
            $choices[1]
        );

        if ($selectedChoice === 'Custom color (enter hex code)') {
            return $this->askForCustomColor($io);
        }

        preg_match('/^(#[A-Fa-f0-9]{6})/', $selectedChoice, $matches);
        $selectedColor = $matches[1];

        $io->success(sprintf('Selected color: %s', $selectedColor));

        return $selectedColor;
    }

    private function askForCustomColor(SymfonyStyle $io): string
    {
        return $io->ask(
            'Please enter a hex color code (e.g. #189EFF):',
            '#189EFF',
            function ($answer) {
                if (empty($answer)) {
                    throw new \RuntimeException('Color cannot be empty.');
                }

                $color = trim($answer);

                if (!str_starts_with($color, '#')) {
                    $color = '#' . $color;
                }

                if (!preg_match('/^#[A-Fa-f0-9]{6}$/', $color)) {
                    throw new \RuntimeException(
                        'Invalid hex color format. Please use format #RRGGBB (e.g. #189EFF)'
                    );
                }

                return strtoupper($color);
            }
        );
    }
}
