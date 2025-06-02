<?php

namespace Shopware\Development\Make\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'dev:make:plugin:admin-module',
    description: 'Generates a Vue admin module for a Shopware plugin'
)]
class MakeAdminModuleCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $test = $io->ask('test');

        $output->writeln($test);

        return 0;
    }
}
