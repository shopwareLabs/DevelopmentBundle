<?php

declare(strict_types=1);

namespace Shopware\Development\Make\Service;

use RuntimeException;

class TemplateService
{

    public const TEMPLATE_PRESETS_DIRECTORY = __DIR__ . '/../Template/';
    public const TEMPLATE_PRESETS_FILE_EXTENSION = '.template';

    public function __construct(
        private readonly FileMergeService $fileMergeService
    )
    {
    }

    public function generateTemplate(string $templateName, array $variables): string
    {
        $templatePath = self::TEMPLATE_PRESETS_DIRECTORY . $templateName;

        if (!file_exists($templatePath)) {
            throw new RuntimeException(sprintf('Template "%s" not found.', $templateName));
        }

        $content = file_get_contents($templatePath);

        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }

        return $content;
    }

    public function getPresetTemplates($directory): array
    {
        $templates = [];
        $templateDir = self::TEMPLATE_PRESETS_DIRECTORY . $directory;


        if (!is_dir($templateDir)) {
            throw new RuntimeException(sprintf('Template directory "%s" does not exist.', $templateDir));
        }

        $files = scandir($templateDir);
        foreach ($files as $file) {
            if (is_file($templateDir . '/' . $file) && str_ends_with($file, self::TEMPLATE_PRESETS_FILE_EXTENSION)) {
                $templateName = str_replace(self::TEMPLATE_PRESETS_FILE_EXTENSION, '', $file);
                $templates[$templateName] = $file;
            }
        }

        if (empty($templates)) {
            throw new RuntimeException(sprintf('No templates found in directory "%s".', $templateDir));
        }

        return $templates;
    }

    public function createFile(string $content, string $targetPath): bool
    {
        $directory = dirname($targetPath);
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new RuntimeException(sprintf('Unable to create directory "%s".', $directory));
            }
        }

        return (bool) file_put_contents($targetPath, $content);
    }

    public function mergeFile(string $template, string $filePath): bool
    {
        $merged = false;
        $content = file_get_contents($filePath);

        if ($content === false) {
            throw new RuntimeException(sprintf('Unable to read file "%s".', $filePath));
        }

        $mergedContent = '';
        if (str_ends_with($filePath, 'services.xml')) {
            $mergedContent = $this->fileMergeService->mergeServicesXml($content, $template);
        } else if (str_ends_with($filePath, 'main.js')) {
            $mergedContent = $this->fileMergeService->mergeMainJS($content, $template);
        }

        if ($mergedContent !== '') {
            if (file_put_contents($filePath, $mergedContent) === false) {
                throw new RuntimeException(sprintf('Unable to write to file "%s".', $filePath));
            } else {
                $merged = true;
            }
        }

        return $merged;
    }
}