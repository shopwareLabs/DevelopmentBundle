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

    protected array $presetTemplates = [];

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
    protected function getPresetTemplates(): array
    {
        if (empty($this->presetTemplates)) {
            $this->presetTemplates = $this->templateService->getPresetTemplates(static::TEMPLATE_DIRECTORY);
        }

        return $this->presetTemplates;
    }

    protected function getPresetTemplateByName(string $type): string
    {
        $templates = $this->getPresetTemplates();

        if (!isset($templates[$type])) {
            throw new RuntimeException(
                sprintf('Template "%s" not found in directory "%s".', $type, static::TEMPLATE_DIRECTORY)
            );
        }

        return static::TEMPLATE_DIRECTORY . '/' . $templates[$type];
    }

    protected function validatePHPClassName(string $className): string
    {
        if (empty($className)) {
            throw new \RuntimeException('Class name cannot be empty.');
        }

        if (!preg_match('/^[a-zA-Z_]/', $className)) {
            throw new \RuntimeException('Class name must start with a letter or underscore.');
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $className)) {
            throw new \RuntimeException('Class name can only contain letters, numbers, and underscores.');
        }

        $reservedKeywords = [
            'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class',
            'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else',
            'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch',
            'endwhile', 'eval', 'exit', 'extends', 'final', 'finally', 'fn', 'for', 'foreach',
            'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once',
            'instanceof', 'insteadof', 'interface', 'isset', 'list', 'match', 'namespace',
            'new', 'or', 'print', 'private', 'protected', 'public', 'require', 'require_once',
            'return', 'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var',
            'while', 'xor', 'yield', '__halt_compiler'
        ];

        if (in_array(strtolower($className), $reservedKeywords)) {
            throw new \RuntimeException("'$className' is a PHP reserved keyword and cannot be used as a class name.");
        }

        return $className;
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
