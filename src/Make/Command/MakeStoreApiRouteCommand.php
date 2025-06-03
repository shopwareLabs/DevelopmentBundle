<?php

declare(strict_types=1);

namespace Shopware\Development\Make\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'dev:make:plugin:store-api-route',
    description: 'Make a store API route for a plugin'
)]
class MakeStoreApiRouteCommand extends AbstractMakeCommand
{

    protected function configure(): void
    {
        $this->setDescription('Generates a store API route for a plugin');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info('Generating a new store API route...');


        $io->success('Store API route generated successfully!');

        return Command::SUCCESS;
    }
}
