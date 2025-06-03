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
    name: 'dev:make:plugin:storefront-controller',
    description: 'Make a storefront controller for a plugin'
)]
class MakeStorefrontControllerCommand extends AbstractMakeCommand
{

    public const TEMPLATE_DIRECTORY = 'storefront-controller';

    public const TEMPLATES = [
        self::TEMPLATE_DIRECTORY => [
            'class' => 'class.template',
            'services' => 'services-xml.template',
            'routes' => 'routes-xml.template',
            'twig' => 'twig.template'
        ]
    ];

    public const CONTROLLER_METHODS = ['GET', 'POST', 'PUT', 'DELETE'];

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Make Storefront Controller Command');

        $variables = $this->validateInput($io);

        $fileName = $variables['CLASSNAME'] . '.php';
        $filePath = $variables['FILEPATH'] . '/' .  $fileName;
        $this->generateContent($io, $this->getTemplateName('class'), $variables, $filePath);

        $fileName = 'services.xml';
        $filePath = $variables['BUNDLEPATH'] . '/Resources/config/' .  $fileName;
        $this->generateContent($io, $this->getTemplateName('services'), $variables, $filePath);

        $fileName = 'routes.xml';
        $filePath = $variables['BUNDLEPATH']  . '/Resources/config/' .   $fileName;
        $this->generateContent($io, $this->getTemplateName('routes'), $variables, $filePath);

        $twigTemplateName = $variables['TWIGTEMPLATE'];
        $filePath = $variables['BUNDLEPATH']  . '/Resources/views/' .   $twigTemplateName;
        $this->generateContent($io, $this->getTemplateName('twig'), $variables, $filePath);

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
            'Controller/Storefront'
        );

        $validatedInput['NAMESPACE'] = $nameSpace['namespace'];
        $validatedInput['TWIGNAMESPACE'] = $nameSpace['name'];
        $validatedInput['FILEPATH'] = $nameSpace['path'];
        $validatedInput['BUNDLEPATH'] = $pluginPath['path'];

        $validatedInput['CLASSNAME'] = $io->ask(
            'Enter the class name (e.g., MyPluginController)',
            'MyPluginController',
            function ($answer) {
                if (empty($answer)) {
                    throw new \RuntimeException('Class name cannot be empty.');
                }

                return trim($answer);
            }
        );

        $validatedInput['ROUTENAME'] = $io->ask(
            'Enter the route name (e.g., my.plugin.controller.action)',
            'my.plugin.controller.action',
            function ($answer) {
                if (empty($answer)) {
                    throw new \RuntimeException('Route name cannot be empty.');
                }

                return trim($answer);
            }
        );

        $validatedInput['ROUTEPATH'] = $io->ask(
            'Enter the route path (e.g., /my/plugin/controller/action)',
            '/my/plugin/controller/action',
            function ($answer) {
                if (empty($answer)) {
                    throw new \RuntimeException('Route name cannot be empty.');
                }

                return trim($answer);
            }
        );

        $question = new ChoiceQuestion('Select method(s):',self::CONTROLLER_METHODS, 0);
        $question->setMultiselect(true);
        $methods = $io->askQuestion($question);

        $validatedInput['METHODS'] = implode(',', array_map(fn($method) => "'" . $method . "'", $methods));

        $validatedInput['TWIGTEMPLATE'] = $io->ask(
            'Enter the Twig template name (e.g., /storefront/page/example.html.twig)',
            '/storefront/page/example.html.twig',
            function ($answer) {
                if (empty($answer)) {
                    throw new \RuntimeException('template name cannot be empty.');
                }

                $valid = preg_match('/^\/storefront\/[a-z0-9_\/]+\.html\.twig$/', $answer);
                if (!$valid) {
                    throw new \RuntimeException(
                        'Invalid Twig template name. It should start with "/storefront/" and end with ".html.twig".'
                    );
                }

                return trim($answer);
            }
        );

        $validatedInput['FUNCTIONNAME'] = lcfirst(
            str_replace(
                ' ',
                '',
                ucwords(
                    str_replace('.', ' ', $validatedInput['ROUTENAME'])
                )
            )
        );

        return $validatedInput;
    }
}
