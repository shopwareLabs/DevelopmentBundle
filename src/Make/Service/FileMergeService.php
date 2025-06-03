<?php

declare(strict_types=1);

namespace Shopware\Development\Make\Service;

use RuntimeException;

class FileMergeService
{

    public function mergeServicesXml(string $originalXml, string $newXml): string
    {

        $originalDom = new \DOMDocument('1.0', 'UTF-8');
        $originalDom->preserveWhiteSpace = false;
        $originalDom->formatOutput = true;
        $originalDom->loadXML($originalXml);

        $newDom = new \DOMDocument('1.0', 'UTF-8');
        $newDom->preserveWhiteSpace = false;
        $newDom->formatOutput = true;
        $newDom->loadXML($newXml);

        $originalServices = $originalDom->getElementsByTagName('service');
        $newServices = $newDom->getElementsByTagName('service');

        $serviceIds = [];

        $mergedDom = new \DOMDocument('1.0', 'UTF-8');
        $mergedDom->preserveWhiteSpace = false;
        $mergedDom->formatOutput = true;

        $rootElement = $mergedDom->createElement('container');
        $rootElement->setAttribute('xmlns', 'http://symfony.com/schema/dic/services');
        $rootElement->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $rootElement->setAttribute('xsi:schemaLocation', 'http://symfony.com/schema/dic/services https://symfony.com/schema/dic/services/services-1.0.xsd');
        $mergedDom->appendChild($rootElement);

        $servicesElement = $mergedDom->createElement('services');
        $rootElement->appendChild($servicesElement);

        foreach ($originalServices as $service) {
            $id = $service->getAttribute('id');
            $serviceIds[$id] = true;

            $importedNode = $mergedDom->importNode($service, true);
            $servicesElement->appendChild($importedNode);
        }

        foreach ($newServices as $service) {
            $id = $service->getAttribute('id');

            if (isset($serviceIds[$id])) {
                continue;
            }

            $importedNode = $mergedDom->importNode($service, true);
            $servicesElement->appendChild($importedNode);
        }

        return $mergedDom->saveXML();
    }

}