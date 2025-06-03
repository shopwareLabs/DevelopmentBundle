<?php

declare(strict_types=1);

namespace Shopware\Development\Make\Command;

use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'dev:make:plugin:admin-component',
    description: 'Generates a Vue admin component for a shopware plugin or bundle',
)]
class MakeAdminComponentCommand extends AbstractMakeCommand
{
    private const DEFAULT_COMPONENT_NAME = 'my-custom-component';
    private const MAX_COMPONENT_NAME_LENGTH = 50;

    private const AVAILABLE_TYPES = [
        'component' => 'A reusable UI component',
        'page' => 'A page component (route endpoint)',
        'view' => 'A view component (sub-section of a page)'
    ];

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Shopware Admin Component Generator');

        $pluginPath = $this->bundleFinder->askForBundle($io);
        $module = $this->findAndPickAdminModule($io, $pluginPath);
        if (!$module) {
            $io->error('No valid module found. Please ensure your plugin has an administration directory with registered modules.');
            return self::FAILURE;
        }

        $componentConfig['name'] = $this->askForComponentName($io);
        $componentConfig['type'] = $this->askForComponentType($io);

        $this->createAdminComponent($io, $module, $componentConfig, $pluginPath);

        return self::SUCCESS;
    }

    private function findAndPickAdminModule(SymfonyStyle $io, array $pluginPath): ?array
    {
        $adminBasePath = Path::join($pluginPath['path'], $this->namespacePickerService::NAMESPACE_ADMINISTRATION_JS);

        if (!file_exists($adminBasePath)) {
            $io->error('No administration directory found in this plugin.');
            return null;
        }

        $finder = new Finder();
        $finder->files()
            ->in($adminBasePath)
            ->name('*.js')
            ->contains('Module.register');

        if (!$finder->hasResults()) {
            $io->warning('No module registrations found in administration path.');
            return null;
        }

        $modules = [];
        foreach ($finder as $file) {
            $relativePath = Path::makeRelative($file->getPath(), $adminBasePath);
            $fullPath = empty($relativePath)
                ? $file->getFilename()
                : $relativePath . '/' . $file->getFilename();

            $modules[$fullPath] = [
                'name' => $file->getFilename(),
                'path' => $fullPath,
                'namespace' => $relativePath,
            ];
        }

        $selectedChoice = $io->choice('Please select an existing module', array_keys($modules));

        return $modules[$selectedChoice];
    }

    private function askForComponentName(SymfonyStyle $io): string
    {
        $io->section('Component Configuration');

        $componentName = $io->ask(
            sprintf('Please enter the name of the component (e.g. "%s"):', self::DEFAULT_COMPONENT_NAME),
            self::DEFAULT_COMPONENT_NAME,
            function ($answer) {
                $this->validateComponentName($answer);
                return trim($answer);
            }
        );

        $io->success(sprintf('Component name set to: %s', $componentName));

        return $componentName;
    }

    private function validateComponentName(string $name): void
    {
        $trimmed = trim($name);

        if (empty($trimmed)) {
            throw new RuntimeException('Component name cannot be empty.');
        }

        if (!preg_match('/^[a-z][a-z0-9-]*$/', $trimmed)) {
            throw new RuntimeException(
                'Component name must start with lowercase letter and contain only lowercase letters, numbers and hyphens.'
            );
        }

        if (!str_contains($trimmed, '-')) {
            throw new RuntimeException(
                'Component name must contain at least one hyphen (-).'
            );
        }

        if (strlen($trimmed) > self::MAX_COMPONENT_NAME_LENGTH) {
            throw new RuntimeException(
                sprintf('Component name cannot be longer than %s characters.', self::MAX_COMPONENT_NAME_LENGTH)
            );
        }
    }

    private function askForComponentType(SymfonyStyle $io): string
    {
        $choices = array_keys(self::AVAILABLE_TYPES);
        $descriptions = array_map(function ($key, $description) {
            return sprintf('%s - %s', $key, $description);
        }, array_keys(self::AVAILABLE_TYPES), array_values(self::AVAILABLE_TYPES));

        $io->section('Component Type');
        $io->listing($descriptions);

        $type = $io->choice(
            'Please select the component type',
            $choices,
            'component'
        );

        $io->success(sprintf('Component type set to: %s', $type));

        return $type;
    }

    private function createAdminComponent(SymfonyStyle $io, array $module, array $componentConfig, array $pluginPath): void
    {
        $componentName = $componentConfig['name'];
        $componentType = $componentConfig['type'];

        // Basis-Pfad für die Komponente
        $basePath = Path::join(
            $pluginPath['path'],
            $this->namespacePickerService::NAMESPACE_ADMINISTRATION_JS,
            $module['namespace'],
            $componentType,
            $componentName
        );

        $variables = [
            'COMPONENT_ID' => $componentName,
            'COMPONENT_ID_SNAKE' => str_replace('-', '_', $componentName),
        ];

        $jsTemplate = $this->getJsTemplate($componentType);
        $this->generateContent($io, $jsTemplate, $variables, "{$basePath}/{$componentName}.js");

        $twigTemplate = $this->getTwigTemplate($componentType);
        $this->generateContent($io, $twigTemplate, $variables, "{$basePath}/{$componentName}.html.twig");
    }

    private function getJsTemplate(string $type): string
    {
        return match($type) {
            'page' => 'administration/page.js.template',
            'view' => 'administration/view.js.template',
            default => 'administration/component.js.template',
        };
    }

    private function getTwigTemplate(string $type): string
    {
        return match($type) {
            'page' => 'administration/page.twig.template',
            default => 'administration/component.twig.template',
        };
    }
}

