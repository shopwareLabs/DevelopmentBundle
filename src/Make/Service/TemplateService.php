<?php

declare(strict_types=1);

namespace Shopware\Development\Make\Service;

use RuntimeException;

class TemplateService
{
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
}