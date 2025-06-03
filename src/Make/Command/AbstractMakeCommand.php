<?php

declare(strict_types=1);

namespace Shopware\Development\Make\Command;

use RuntimeException;
use Shopware\Development\Make\Service\BundleFinderService;
use Shopware\Development\Make\Service\NamespacePickerService;
use Shopware\Development\Make\Service\TemplateService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

abstract class AbstractMakeCommand extends Command
{
    protected Filesystem $fileSystem;

    public function __construct(
        protected readonly BundleFinderService    $bundleFinder,
        protected readonly NamespacePickerService $namespacePickerService,
        protected readonly TemplateService        $templateService,
    ) {
        $this->fileSystem = new Filesystem();
        parent::__construct();
    }

    protected function generateContent(
        SymfonyStyle $io,
        string $templateName,
        array $variables,
        string $filePath
    ): void {
        $template = $this->templateService->generateTemplate($templateName, $variables);
        $path = explode('/', $filePath);
        $fileName = $path[count($path) - 1];

        if (!$this->templateService->createFile($template, $filePath)) {
            throw new RuntimeException(sprintf('Failed to create file at: %s', $filePath));
        }

        $io->success(sprintf('File created successfully at: %s', $filePath));
        $io->text([
            'Details:',
            sprintf('- File: %s', $fileName),
            sprintf("- Content:\n%s", $template),
        ]);
    }
}
