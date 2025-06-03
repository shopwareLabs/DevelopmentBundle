<?php declare(strict_types=1);

namespace Shopware\Development\Make\Command;

use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'dev:make:plugin:event-subscriber',
    description: 'Creates a new event subscriber class in a plugin'
)]
class MakeEventSubscriberCommand extends AbstractMakeCommand
{
    public const TEMPLATE_DIRECTORY = 'event-subscriber';
    public const TEMPLATES = [
        self::TEMPLATE_DIRECTORY => [
            'class' => 'class.template',
            'services' => 'services-xml.template'
        ]
    ];

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Make Event Subscriber Command');

        $variables = $this->validateInput($io);

        $fileName = $variables['CLASSNAME'] . '.php';
        $filePath = $variables['FILEPATH'] . '/' . $fileName;
        $this->generateContent($io, $this->getTemplateName('class'), $variables, $filePath);

        $fileName = 'services.xml';
        $filePath = $variables['BUNDLEPATH'] . '/Resources/config/' . $fileName;
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
            'Subscriber'
        );

        $validatedInput['NAMESPACE'] = $nameSpace['namespace'];
        $validatedInput['TWIGNAMESPACE'] = $nameSpace['name'];
        $validatedInput['FILEPATH'] = $nameSpace['path'];
        $validatedInput['BUNDLEPATH'] = $pluginPath['path'];

        $validatedInput['CLASSNAME'] = $io->ask(
            'Enter the subscriber class name including the "Subscriber" at the end (e.g., MySubscriber)',
            'MySubscriber',
            fn($answer) => trim($answer) ?: throw new RuntimeException('Class name cannot be empty.')
        );

        return $validatedInput;
    }
}
