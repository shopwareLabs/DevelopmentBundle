<?php

declare(strict_types=1);

namespace Shopware\Development\Make\Command;

use Shopware\Development\Make\Service\BundleFinder;
use Shopware\Development\Make\Service\NamespacePickerService;
use Symfony\Component\Console\Command\Command;


abstract class AbstractMakeCommand extends Command
{
    public function __construct(
        protected readonly BundleFinder $bundleFinder,
        protected readonly NamespacePickerService $namespacePickerService
    ) {
        parent::__construct();
    }
}