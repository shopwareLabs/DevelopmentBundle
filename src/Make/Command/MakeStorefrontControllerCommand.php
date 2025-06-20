<?php

declare(strict_types=1);

namespace Shopware\Development\Make\Command;

use Shopware\Core\PlatformRequest;
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

    public const CONTROLLER_METHODS = ['GET', 'POST', 'PUT', 'DELETE'];

    public const ADDITIONAL_PARAMETERS = [
        'options' => self::OPTIONS,
        'defaults' => self::DEFAULTS,
    ];
    public const OPTIONS = [
        'seo' => ['true','false']
    ];

    public const DEFAULTS = [
        'XmlHttpRequest' => ['true','false'],
        PlatformRequest::ATTRIBUTE_LOGIN_REQUIRED => ['true','false'],
        PlatformRequest::ATTRIBUTE_LOGIN_REQUIRED_ALLOW_GUEST => ['true','false'],
        PlatformRequest::ATTRIBUTE_CAPTCHA => ['true','false'],
        PlatformRequest::ATTRIBUTE_HTTP_CACHE => ['true','false'],
        PlatformRequest::ATTRIBUTE_NO_STORE => ['true','false'],
        PlatformRequest::ATTRIBUTE_IS_ALLOWED_IN_MAINTENANCE => ['true','false']
    ];

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Make Storefront Controller Command');

        $variables = $this->validateInput($io);

        $fileName = $variables['CLASSNAME'] . '.php';
        $filePath = $variables['FILEPATH'] . '/' .  $fileName;
        $this->generateContent($io, $this->getPresetTemplateByName('class'), $variables, $filePath);

        $fileName = 'services.xml';
        $filePath = $variables['BUNDLEPATH'] . '/Resources/config/' .  $fileName;
        $this->generateContent($io, $this->getPresetTemplateByName('services-xml'), $variables, $filePath);

        $fileName = 'routes.xml';
        $filePath = $variables['BUNDLEPATH']  . '/Resources/config/' .   $fileName;
        $this->generateContent($io, $this->getPresetTemplateByName('routes-xml'), $variables, $filePath);

        $twigTemplateName = $variables['TWIGTEMPLATE'];
        $filePath = $variables['BUNDLEPATH']  . '/Resources/views/' .   $twigTemplateName;
        $this->generateContent($io, $this->getPresetTemplateByName('twig'), $variables, $filePath);

        return Command::SUCCESS;
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

        $validatedInput['CLASSNAME'] = $this->askPHPClassName($io, 'ExamplePluginController');
        $validatedInput['ROUTENAME'] = $this->askRouteName($io, 'example.plugin.controller');
        $validatedInput['ROUTEPATH'] = $this->askRoutePath($io, '/example/plugin/controller/{id}');

        $validatedInput['METHODS'] = $this->askMethods($io, 0);
        $validatedInput['OPTIONS'] = $this->askadditionalParameters($io, 'options');
        $validatedInput['DEFAULTS'] = $this->askadditionalParameters($io, 'defaults');

        $validatedInput['TWIGTEMPLATE'] = $this->askTwigStorefrontTemplate($io, '/storefront/page/example/index.html.twig');

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

    public function askRouteName(SymfonyStyle $io, string $defaultRouteName = 'example.controller.action'): string
    {
        return $io->ask(
            'Enter the route name (e.g., ' . $defaultRouteName . ')',
            $defaultRouteName,
            function ($answer) use ($defaultRouteName) {
                if (empty($answer)) {
                    throw new \RuntimeException('Route name cannot be empty.');
                }

                if (!preg_match('/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+$/', $answer)) {
                    throw new \RuntimeException(
                        'Invalid route name format. It should be lowercase, use only alphanumeric characters and underscores, ' .
                        'with segments separated by dots (e.g., ' . $defaultRouteName . ').'
                    );
                }

                $segments = explode('.', $answer);
                if (count($segments) < 2) {
                    throw new \RuntimeException(
                        'Route name should have at least 2 segments separated by dots (e.g., ' . $defaultRouteName . ').'
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

                return trim($answer);
            }
        );
    }

    public function askRoutePath(SymfonyStyle $io,  string $defaultRoutePath = 'example/controller/action'): string
    {
        return $io->ask(
            'Enter the route path (e.g., ' . $defaultRoutePath . ')',
            $defaultRoutePath,
            function ($answer) {
                return $this->validateRoutePath($answer);
            }
        );
    }

    public function askMethods(SymfonyStyle $io, int $defaultMethod = 0): string
    {
        $question = new ChoiceQuestion('Select method(s):',self::CONTROLLER_METHODS, $defaultMethod);
        $question->setMultiselect(true);
        $methods = $io->askQuestion($question);

        return implode(',', array_map(fn($method) => "'" . $method . "'", $methods));
    }

    private function askAdditionalParameters(SymfonyStyle $io, string $type): string
    {
        $flattenedParams = '';
        $parameterPresets = self::ADDITIONAL_PARAMETERS[$type] ?? [];

        if (!empty($parameterPresets)) {

            $addOptionalParams = $io->confirm(
                'Do you want to add optional Route ' . $type . ' parameters?',
                false
            );

            if ($addOptionalParams === true) {

                $io->section('Available ' . $type . ' parameters:');

                foreach ($parameterPresets as $key => $values) {
                    $io->writeln(sprintf(
                        '%s: %s',
                        $key,
                        implode('|', $values)
                    ));
                }

                $selectedParams = [];
                $choices = array_keys($parameterPresets);
                $defaultChoice = array_key_first($choices);

                $question = new ChoiceQuestion(
                    'Select ' . $type . ' to configure',
                    $choices,
                    $defaultChoice
                );

                $question->setMultiselect(true);
                $selectedKeys = $io->askQuestion($question);

                foreach ($selectedKeys as $key) {
                    $values = $parameterPresets[$key];
                    $defaultValue = array_key_first($values);

                    $subQuestion = new ChoiceQuestion(
                        sprintf('Select value for %s:', $key),
                        $values,
                        $defaultValue
                    );

                    $selectedValue = $io->askQuestion($subQuestion);
                    $selectedParams[$key] = $selectedValue;
                }

                if (!empty($selectedParams)) {
                    $flattenedParams = ', ' . $type . ': [';
                    $optionStrings = [];

                    foreach ($selectedParams as $key => $value) {
                        $optionStrings[] = "'" . $key . "' => " . $value;
                    }

                    $flattenedParams .= implode(', ', $optionStrings) . "]";
                }
            }
        }

        return $flattenedParams;
    }

    public function askTwigStorefrontTemplate(SymfonyStyle $io, string $defaultTwigTemplate = '/storefront/page/example.html.twig'): string
    {
        return $io->ask(
            'Enter the Twig template name (e.g., ' . $defaultTwigTemplate . ')',
            $defaultTwigTemplate,
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
    }

    private function validateRoutePath(string $routePath): string
    {
        if (empty($routePath)) {
            throw new \RuntimeException('Route path cannot be empty.');
        }

        if (!str_starts_with($routePath, '/')) {
            throw new \RuntimeException('Route path must start with a slash (/).');
        }

        if (str_ends_with($routePath, '/') && strlen($routePath) > 1) {
            throw new \RuntimeException('Route path should not end with a slash.');
        }

        if (strpos($routePath, '//') !== false) {
            throw new \RuntimeException('Route path cannot contain consecutive slashes.');
        }

        $segments = array_filter(explode('/', $routePath));

        if (empty($segments)) {
            throw new \RuntimeException('Route path must contain at least one segment after the leading slash.');
        }

        foreach ($segments as $segment) {
            if (preg_match('/^\{([a-zA-Z][a-zA-Z0-9]*)\??}$/', $segment)) {
                continue;
            }

            if (preg_match('/^[a-z0-9\-]+\{([a-zA-Z][a-zA-Z0-9]*)\??}$/', $segment)) {
                continue;
            }

            if (!preg_match('/^[a-z][a-z0-9\-]*$/', $segment)) {
                throw new \RuntimeException(
                    'Each segment must start with a lowercase letter and contain only lowercase letters, numbers, and hyphens, ' .
                    'or be a valid parameter pattern like {id} or prefix-{id}.'
                );
            }
        }

        return $routePath;
    }
}





