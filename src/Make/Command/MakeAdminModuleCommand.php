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
    description: 'Generates a Vue admin module for a shopware plugin or bundle',
)]
class MakeAdminModuleCommand extends AbstractMakeCommand
{
    private const DEFAULT_MODULE_NAME = 'MyCustomModule';
    private const DEFAULT_COLOR = '#189EFF';
    private const MAX_MODULE_NAME_LENGTH = 50;
    private const CUSTOM_COLOR_OPTION = 'Custom color (enter hex code)';

    private const AVAILABLE_PARENTS = [
        'sw-product' => 'Products',
        'sw-order' => 'Orders',
        'sw-customer' => 'Customers',
        'sw-cms' => 'Content Management',
        'sw-analytics' => 'Analytics & Reports',
        'sw-extension' => 'Extensions & Apps',
        'sw-settings' => 'Settings',
    ];

    private const SHOPWARE_COLORS = [
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Shopware Admin Module Generator');

        $pluginPath = $this->bundleFinder->askForBundle($io);
        $moduleConfig = $this->namespacePickerService->pickAdminNamespace($io, $pluginPath, 'module/my-module');

        $moduleConfig['name'] = $this->askForModuleName($io);
        $moduleConfig['parent'] = $this->askForModuleParent($io);
        $moduleConfig['color'] = $this->askForModuleColor($io);

        $this->createAdminModule($moduleConfig, $io);

        return self::SUCCESS;
    }

    private function askForModuleName(SymfonyStyle $io): string
    {
        $io->section('Module Configuration');

        $moduleName = $io->ask(
            'Please enter the name of the module (e.g. "MyCustomModule"):',
            self::DEFAULT_MODULE_NAME,
            function ($answer) {
                $this->validateModuleName($answer);
                return trim($answer);
            }
        );

        $io->success(sprintf('Module name set to: %s', $moduleName));

        return $moduleName;
    }

    private function askForModuleParent(SymfonyStyle $io): string
    {
        $io->section('Select Parent Module');
        $io->text('Choose the parent module where your new module should be located:');
        $io->newLine();

        $choices = $this->formatParentChoices();

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

    private function askForModuleColor(SymfonyStyle $io): string
    {
        $io->section('Select Module Color');
        $io->text('Choose a color for your module (used for icons, highlights, etc.):');
        $io->newLine();

        $choices = $this->formatColorChoices();

        $selectedChoice = $io->choice(
            'Please select a color:',
            $choices,
            $choices[1]
        );

        if ($selectedChoice === self::CUSTOM_COLOR_OPTION) {
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
            self::DEFAULT_COLOR,
            function ($answer) {
                return $this->validateAndFormatHexColor($answer);
            }
        );
    }

    private function validateModuleName(string $name): void
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

    private function validateAndFormatHexColor(string $color): string
    {
        $trimmed = trim($color);

        if (empty($trimmed)) {
            throw new RuntimeException('Color cannot be empty.');
        }

        if (!str_starts_with($trimmed, '#')) {
            $trimmed = '#' . $trimmed;
        }

        if (!preg_match('/^#[A-Fa-f0-9]{6}$/', $trimmed)) {
            throw new RuntimeException(
                'Invalid hex color format. Please use format #RRGGBB (e.g. #189EFF)'
            );
        }

        return strtoupper($trimmed);
    }

    private function formatParentChoices(): array
    {
        $choices = [];
        foreach (self::AVAILABLE_PARENTS as $key => $description) {
            $choices[] = sprintf('%s - %s', $key, $description);
        }
        return $choices;
    }

    private function formatColorChoices(): array
    {
        $choices = [self::CUSTOM_COLOR_OPTION];

        foreach (self::SHOPWARE_COLORS as $hex => $description) {
            $choices[] = sprintf('%-5s - %s', $hex, $description);
        }

        return $choices;
    }

    private function createAdminModule(array $moduleConfig, SymfonyStyle $io): void
    {
        $modulePath = $moduleConfig['path'];
        $moduleName = $moduleConfig['name'];

        $moduleFileName = $this->convertModuleName($moduleName) . '.js';
        $moduleFilePath = $modulePath . '/' . $moduleFileName;

        $variables = [
            'MODULE_NAME' => $moduleName,
            'MODULE_ID' => $this->convertModuleName($moduleName),
            'PARENT' => $moduleConfig['parent'],
            'COLOR' => $moduleConfig['color'],
        ];

        $this->generateContent($io, 'admin-module.template', $variables, $moduleFileName, $moduleFilePath);
    }

    private function convertModuleName(string $input): string
    {
        $input = trim($input);

        $input = preg_replace('/[^a-zA-Z0-9\s]/', '', $input);
        $input = preg_replace('/\s+/', ' ', $input);
        $input = str_replace(' ', '-', $input);
        $input = preg_replace('/([a-z])([A-Z])/', '$1-$2', $input);

        return strtolower($input);
    }
}
