<?php

declare(strict_types=1);

namespace Shopware\Development\Make\Command;

use Shopware\Development\Make\Service\BundleFinder;
use Shopware\Development\Make\Service\NamespacePickerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Filesystem\Filesystem;

abstract class AbstractMakeCommand extends Command
{
    protected Filesystem $fileSystem;

    public function __construct(
        protected readonly BundleFinder $bundleFinder,
        protected readonly NamespacePickerService $namespacePickerService
    ) {
        $this->fileSystem = new Filesystem();
        parent::__construct();
    }
}
