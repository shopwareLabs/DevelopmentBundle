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
    name: 'dev:make:scheduled-task',
    description: 'Creates a new scheduled task for a plugin'
)]
class MakeScheduledTaskCommand extends AbstractMakeCommand
{
    public const TEMPLATE_DIRECTORY = 'scheduled-task';

    public const TEMPLATES = [
        self::TEMPLATE_DIRECTORY => [
            'class' => 'class.template',
            'handler-class' => 'handler-class.template',
            'services' => 'services-xml.template'
        ]
    ];
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
        $this->generateContent($io, $this->getTemplateName('class'), $variables, $filePath);

        $fileName = $variables['HANDLERCLASSNAME'] . '.php';
        $filePath = $variables['FILEPATH'] . '/' .  $fileName;
        $this->generateContent($io, $this->getTemplateName('handler-class'), $variables, $filePath);

        $fileName = 'services.xml';
        $filePath = $variables['BUNDLEPATH'] . '/Resources/config/' .  $fileName;
        $this->generateContent($io, $this->getTemplateName('services'), $variables, $filePath);

        return Command::SUCCESS;
    }

    private function getTemplateName(string $type): string
    {
        return self::TEMPLATE_DIRECTORY . '/' . self::TEMPLATES[self::TEMPLATE_DIRECTORY][$type];
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

        $validatedInput['CLASSNAME'] = $io->ask(
            'Enter the scheduled task name (e.g., CleanupOldDataTask)',
            'ExampleScheduledTask',
            function ($answer) {
                return $this->validatePHPClassName($answer);
            }
        );

        $validatedInput['TASKIDENTIFIER'] = $io->ask(
            'Enter the task identifier (e.g.,example.cleanup_old_data)',
            'example.cleanup_old_data',
            function ($answer) {
                return $this->validateTaskIdentifier($answer);
            }
        );

        $question = new ChoiceQuestion('Select interval:',self::INTERVAL_CHOICES, 'daily');
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

        $validatedInput['INTERVAL'] = $interval;

        $validatedInput['HANDLERCLASSNAME'] = $io->ask(
            'Enter the scheduled task handler name (e.g., CleanupOldDataTaskHandler)',
            'ExampleScheduledTaskHandler',
            function ($answer) {
                return $this->validatePHPClassName($answer);
            }
        );

        return $validatedInput;
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

    private function validatePHPClassName(string $className): string
    {
        if (empty($className)) {
            throw new \RuntimeException('Class name cannot be empty.');
        }

        if (!preg_match('/^[a-zA-Z_]/', $className)) {
            throw new \RuntimeException('Class name must start with a letter or underscore.');
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $className)) {
            throw new \RuntimeException('Class name can only contain letters, numbers, and underscores.');
        }

        $reservedKeywords = [
            'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class',
            'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else',
            'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch',
            'endwhile', 'eval', 'exit', 'extends', 'final', 'finally', 'fn', 'for', 'foreach',
            'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once',
            'instanceof', 'insteadof', 'interface', 'isset', 'list', 'match', 'namespace',
            'new', 'or', 'print', 'private', 'protected', 'public', 'require', 'require_once',
            'return', 'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var',
            'while', 'xor', 'yield', '__halt_compiler'
        ];

        if (in_array(strtolower($className), $reservedKeywords)) {
            throw new \RuntimeException("'$className' is a PHP reserved keyword and cannot be used as a class name.");
        }

        return $className;
    }

}
