<?php

declare(strict_types=1);

namespace Shopware\Development\Make\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'dev:make:plugin:scheduled-task',
    description: 'Creates a new scheduled task for a plugin'
)]
class MakeScheduledTaskCommand extends AbstractMakeCommand
{
    public const TEMPLATE_DIRECTORY = 'scheduled-task';

    public const INTERVAL_CHOICES = [
        'minutely',
        'hourly',
        'daily',
        'weekly',
        'custom',
    ];

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Make Scheduled Task Command');

        $variables = $this->validateInput($io);

        $fileName = $variables['CLASSNAME'] . '.php';
        $filePath = $variables['FILEPATH'] . '/' .  $fileName;
        $this->generateContent($io, $this->getPresetTemplateByName('class'), $variables, $filePath);

        $fileName = $variables['HANDLERCLASSNAME'] . '.php';
        $filePath = $variables['FILEPATH'] . '/' .  $fileName;
        $this->generateContent($io, $this->getPresetTemplateByName('handler-class'), $variables, $filePath);

        $fileName = 'services.xml';
        $filePath = $variables['BUNDLEPATH'] . '/Resources/config/' .  $fileName;
        $this->generateContent($io, $this->getPresetTemplateByName('services-xml'), $variables, $filePath);

        return Command::SUCCESS;
    }


    private function validateInput(SymfonyStyle $io): array
    {
        $validatedInput = [];

        $pluginPath = $this->bundleFinder->askForBundle($io);
        $nameSpace = $this->namespacePickerService->pickNamespace(
            $io,
            $pluginPath,
            'Storefront/ScheduledTask'
        );

        $validatedInput['NAMESPACE'] = $nameSpace['namespace'];
        $validatedInput['TWIGNAMESPACE'] = $nameSpace['name'];
        $validatedInput['FILEPATH'] = $nameSpace['path'];
        $validatedInput['BUNDLEPATH'] = $pluginPath['path'];

        $validatedInput['CLASSNAME'] = $this->askPHPClassName($io, 'ExampleScheduledTask');
        $validatedInput['TASKIDENTIFIER'] = $this->askIdentifier($io, 'example.scheduled_task');
        $validatedInput['INTERVAL'] = $this->askInterval($io);
        $validatedInput['HANDLERCLASSNAME'] = $this->askPHPClassName($io, $validatedInput['CLASSNAME'] . 'Handler');

        return $validatedInput;
    }

    public function askPHPClassName(SymfonyStyle $io, $defaultClassName = 'ExampleClass'): string
    {
        return $io->ask(
            'Enter the scheduled task name (e.g., ' . $defaultClassName .')',
            $defaultClassName,
            function ($answer) {
                return $this->validatePHPClassName($answer);
            }
        );
    }

    public function askIdentifier(SymfonyStyle $io, $defaultIdentifier = 'example.identifier'): string
    {
        return $io->ask(
            'Enter the task identifier (e.g.,' . $defaultIdentifier . ')',
            $defaultIdentifier,
            function ($answer) {
                return $this->validateTaskIdentifier($answer);
            }
        );
    }

    public function askInterval(SymfonyStyle $io, $defaultInterval = 'hourly'): string
    {
        $question = new ChoiceQuestion(
            'Select interval:',
            self::INTERVAL_CHOICES,
            $defaultInterval
        );
        $interval = $io->askQuestion($question);

        if($interval === 'custom') {
            $interval = $io->ask(
                'Enter the custom interval in seconds (e.g., 3600 for hourly)',
                '3600',
                function ($answer) {
                    if (!is_numeric($answer) || (int)$answer <= 0) {
                        throw new \RuntimeException('Interval must be a positive number.');
                    }
                    return (int)$answer;
                }
            );
        } else {
            $interval = 'self::' . strtoupper($interval);
        }

        return $interval;
    }

    private function validateTaskIdentifier(string $identifier): string
    {
        if (empty($identifier)) {
            throw new \RuntimeException('Task identifier cannot be empty.');
        }

        if (!preg_match('/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+$/', $identifier)) {
            throw new \RuntimeException(
                'Invalid task identifier format. It should be lowercase, use only alphanumeric characters and underscores, ' .
                'with segments separated by dots (e.g., vendor.task_name).'
            );
        }

        $segments = explode('.', $identifier);
        if (count($segments) < 2) {
            throw new \RuntimeException(
                'Task identifier should have at least 2 segments separated by dots (e.g., vendor.task_name).'
            );
        }

        foreach ($segments as $segment) {
            if (!preg_match('/^[a-z][a-z0-9_]*$/', $segment)) {
                throw new \RuntimeException(
                    'Each segment must start with a lowercase letter and contain only ' .
                    'lowercase letters, numbers, and underscores.'
                );
            }
        }

        return $identifier;
    }
}
