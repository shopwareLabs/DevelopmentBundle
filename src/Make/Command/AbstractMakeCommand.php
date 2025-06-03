<?php

declare(strict_types=1);

namespace Shopware\Development\Make\Command;

use RuntimeException;
use Shopware\Development\Make\Service\BundleFinderService;
use Shopware\Development\Make\Service\NamespacePickerService;
use Shopware\Development\Make\Service\TemplateService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

abstract class AbstractMakeCommand extends Command
{
    public const FILE_EXISTS_OPTIONS = [
        'overwrite' => 'Overwrite existing file',
        'merge' => 'Merge existing file',
        'skip' => 'Skip file creation',
    ];

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

        if ($this->fileSystem->exists($filePath)) {
            $question = new ChoiceQuestion(sprintf('File already exists (%s). Please choose handling:', $fileName),self::FILE_EXISTS_OPTIONS, 'skip');
            $question->setMultiselect(false);
            $fileHandling = $io->askQuestion($question);

            if ($fileHandling === 'overwrite') {
                $this->writeFile($template, $filePath, $io, $fileName);
            } elseif ($fileHandling === 'merge') {
                $this->mergeFile($template, $filePath, $io, $fileName);
            } else {
                $io->warning(sprintf('Skipping file creation for: %s', $fileName));
            }

        } else {
            $this->writeFile($template, $filePath, $io, $fileName);
        }
    }

    private function writeFile($template, $filePath, $io, $fileName): void
    {
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

    private function mergeFile(string $template, string $filePath, SymfonyStyle  $io, string $fileName): void
    {
        if ($this->templateService->mergeFile($template, $filePath) === false) {
            $io->warning(sprintf('Failed to merge file at: %s, skipping', $filePath));
        } else {
            $io->success(sprintf('File merged successfully at: %s', $filePath));
            $io->text([
                'Details:',
                sprintf('- File: %s', $fileName)
            ]);
        }
    }

}
