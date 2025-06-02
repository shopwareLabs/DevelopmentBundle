<?php declare(strict_types=1);

namespace Shopware\Development\Make\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'dev:make:scheduled-task',
    description: 'Creates a new scheduled task for a plugin'
)]
class MakeScheduledTaskCommand extends AbstractMakeCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $bundle = $this->bundleFinder->askForBundle($io);

        // Ask for task name
        $taskName = $io->ask(
            'Enter the scheduled task name (e.g., CleanupOldData)',
            null,
            function ($answer) {
                if (empty($answer)) {
                    throw new \RuntimeException('Task name cannot be empty.');
                }
                if (!preg_match('/^[A-Z][a-zA-Z0-9]*$/', $answer)) {
                    throw new \RuntimeException('Task name must start with uppercase letter and contain only alphanumeric characters.');
                }
                return $answer;
            }
        );

        // Ask for interval
        $interval = $io->choice(
            'Select interval',
            ['minutely', 'hourly', 'daily', 'weekly'],
            'daily'
        );

        $data = $this->namespacePickerService->pickNamespace($io, $bundle['path'], 'ScheduledTask');

        // Generate the scheduled task
        $this->generateScheduledTask($data, $taskName, $interval, $io);

        $io->success(sprintf('Scheduled task "%s" has been created successfully!', $taskName));
        $io->note('Don\'t forget to register the task and handler in your services.xml file.');

        return Command::SUCCESS;
    }

    /**
     * @param array{fullPath: string, namespace: string} $bundle
     */
    private function generateScheduledTask(array $bundle, string $taskName, string $interval, SymfonyStyle $io): void
    {
        $fs = new Filesystem();
        $scheduledTaskDir = $bundle['fullPath'];
        if (!$fs->exists($scheduledTaskDir)) {
            $fs->mkdir($scheduledTaskDir);
        }

        // Generate task class
        $taskClassName = $taskName . 'Task';
        $taskFilePath = $scheduledTaskDir . '/' . $taskClassName . '.php';
        $fs->dumpFile($taskFilePath, $this->generateTaskClass($bundle['namespace'], $taskClassName, $taskName, $interval));

        // Generate handler class
        $handlerClassName = $taskName . 'TaskHandler';
        $handlerFilePath = $scheduledTaskDir . '/' . $handlerClassName . '.php';
        $fs->dumpFile($handlerFilePath, $this->generateHandlerClass($bundle['namespace'], $taskClassName, $handlerClassName));

        $io->note([
            sprintf('Created task: %s', $taskFilePath),
            sprintf('Created handler: %s', $handlerFilePath),
        ]);

        // Show service configuration
        $io->section('Add the following to your services.xml:');
        $io->text($this->generateServiceConfiguration($bundle['namespace'], $taskClassName, $handlerClassName));
    }

    private function generateTaskClass(string $namespace, string $className, string $taskName, string $interval): string
    {
        $intervalConstant = 'self::' . strtoupper($interval);
        $taskIdentifier = $this->camelCaseToSnakeCase($taskName);

        return <<<PHP
<?php declare(strict_types=1);

namespace $namespace\\ScheduledTask;

use Shopware\\Core\\Framework\\MessageQueue\\ScheduledTask\\ScheduledTask;

class $className extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return '$taskIdentifier.task';
    }

    public static function getDefaultInterval(): int
    {
        return $intervalConstant;
    }
}
PHP;
    }

    private function generateHandlerClass(string $namespace, string $taskClass, string $handlerClass): string
    {
        return <<<PHP
<?php declare(strict_types=1);

namespace $namespace\\ScheduledTask;

use Psr\\Log\\LoggerInterface;
use Shopware\\Core\\Framework\\DataAbstractionLayer\\EntityRepository;
use Shopware\\Core\\Framework\\MessageQueue\\ScheduledTask\\ScheduledTaskHandler;
use Symfony\\Component\\Messenger\\Attribute\\AsMessageHandler;

#[AsMessageHandler(handles: $taskClass::class)]
class $handlerClass extends ScheduledTaskHandler
{
    public function __construct(
        EntityRepository \$scheduledTaskRepository,
        private readonly LoggerInterface \$logger
    ) {
        parent::__construct(\$scheduledTaskRepository, \$logger);
    }

    public function run(): void
    {
        // TODO: Implement your scheduled task logic here
        \$this->logger->info('Running scheduled task');
    }
}
PHP;
    }

    private function generateServiceConfiguration(string $namespace, string $taskClass, string $handlerClass): string
    {
        $taskService = $namespace . '\\ScheduledTask\\' . $taskClass;
        $handlerService = $namespace . '\\ScheduledTask\\' . $handlerClass;

        return <<<XML
<service id="$taskService">
    <tag name="shopware.scheduled.task"/>
</service>

<service id="$handlerService">
    <argument type="service" id="scheduled_task.repository"/>
    <argument type="service" id="logger"/>
    <tag name="messenger.message_handler"/>
</service>
XML;
    }

    private function camelCaseToSnakeCase(string $input): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }
}
