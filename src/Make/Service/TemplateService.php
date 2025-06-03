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

    public function mergeFile($template, $filePath): bool
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

    public function mergeServicesXml(string $originalXml, string $newXml): string
    {
        dump([
            'originalXml' => $originalXml,
            'newXml' => $newXml,
        ]);
        // Parse the XML files
        $originalDom = new \DOMDocument('1.0', 'UTF-8');
        $originalDom->preserveWhiteSpace = false;
        $originalDom->formatOutput = true;
        $originalDom->loadXML($originalXml);

        $newDom = new \DOMDocument('1.0', 'UTF-8');
        $newDom->preserveWhiteSpace = false;
        $newDom->formatOutput = true;
        $newDom->loadXML($newXml);

        // Get all services from both files
        $originalServices = $originalDom->getElementsByTagName('service');
        $newServices = $newDom->getElementsByTagName('service');

        // Track service IDs to avoid duplicates
        $serviceIds = [];

        // Create a new merged document with the same structure
        $mergedDom = new \DOMDocument('1.0', 'UTF-8');
        $mergedDom->preserveWhiteSpace = false;
        $mergedDom->formatOutput = true;

        // Create root container element with namespaces
        $rootElement = $mergedDom->createElement('container');
        $rootElement->setAttribute('xmlns', 'http://symfony.com/schema/dic/services');
        $rootElement->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $rootElement->setAttribute('xsi:schemaLocation', 'http://symfony.com/schema/dic/services https://symfony.com/schema/dic/services/services-1.0.xsd');
        $mergedDom->appendChild($rootElement);

        // Create services container
        $servicesElement = $mergedDom->createElement('services');
        $rootElement->appendChild($servicesElement);

        // Import all services from original XML
        foreach ($originalServices as $service) {
            $id = $service->getAttribute('id');
            $serviceIds[$id] = true;

            $importedNode = $mergedDom->importNode($service, true);
            $servicesElement->appendChild($importedNode);
        }

        // Import services from new XML that don't exist in the original
        foreach ($newServices as $service) {
            $id = $service->getAttribute('id');

            // Skip if this service ID already exists
            if (isset($serviceIds[$id])) {
                continue;
            }

            $importedNode = $mergedDom->importNode($service, true);
            $servicesElement->appendChild($importedNode);
        }

        return $mergedDom->saveXML();
    }

}