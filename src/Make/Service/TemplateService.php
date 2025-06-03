<?php

declare(strict_types=1);

namespace Shopware\Development\Make\Service;

use RuntimeException;

class TemplateService
{

    public function __construct(
        private readonly FileMergeService $fileMergeService
    )
    {
    }

    public function generateTemplate(string $templateName, array $variables): string
    {
        $templatePath = __DIR__ . '/../Template/' . $templateName;

        if (!file_exists($templatePath)) {
            throw new RuntimeException(sprintf('Template "%s" not found.', $templateName));
        }

        $content = file_get_contents($templatePath);

        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }

        return $content;
    }

    public function createFile(string $content, string $targetPath): bool
    {
        $directory = dirname($targetPath);
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0777, true)) {
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