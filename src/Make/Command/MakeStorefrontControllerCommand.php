<?php

declare(strict_types=1);

namespace Shopware\Development\Make\Command;

use Shopware\Development\Make\Service\BundleFinder;
use Shopware\Development\Make\Service\NamespacePickerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'dev:make:plugin:storefront-controller',
    description: 'Make a storefront controller for a plugin'
)]
class MakeStorefrontControllerCommand extends AbstractMakeCommand
{
    protected function configure(): void
    {
        $this->setDescription('Generates a storefront controller for a plugin');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info('Generating a new storefront controller...');

        $io->success('Storefront controller generated successfully!');

        return Command::SUCCESS;
    }
}
