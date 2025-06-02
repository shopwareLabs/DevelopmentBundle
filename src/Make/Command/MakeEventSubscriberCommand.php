<?php declare(strict_types=1);

namespace Shopware\Development\Make\Command;

use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(
    name: 'dev:make:plugin:event-subscriber',
    description: 'Adds an example event subscriber to an existing plugin'
)]
class MakeEventSubscriberCommand extends AbstractMakeCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $plugin = $this->getPluginData($input,$output);
        $namespace = $plugin['name'];
        $basePath = $plugin['path'];

        if (!$this->confirm($input, $output, 'Do you want to create an example event subscriber?')) {
            $output->writeln('<info>Aborted. No subscriber created.</info>');
            return self::SUCCESS;
        }

        $this->generateSubscriberClass($basePath, $namespace);
        $this->appendServiceXml($basePath, $namespace);

        $output->writeln('<info>Event subscriber successfully created in plugin "' . $namespace . '"</info>');
        return self::SUCCESS;
    }

    private function generateSubscriberClass(string $basePath, string $namespace): void
    {
        $dir = $basePath . '/src/Subscriber';
        $path = $dir . '/MySubscriber.php';

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $code = <<<PHP
<?php declare(strict_types=1);

namespace {$namespace}\Subscriber;

use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Content\Product\ProductEvents;

class MySubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ProductEvents::PRODUCT_LOADED_EVENT => 'onProductsLoaded'
        ];
    }

    public function onProductsLoaded(EntityLoadedEvent \$event): void
    {
        // Do something with \$event->getEntities()
    }
}
PHP;

        file_put_contents($path, $code);
    }

    private function appendServiceXml(string $basePath, string $namespace): void
    {
        $configDir = $basePath . '/src/Resources/config';
        $servicesPath = $configDir . '/services.xml';

        if (!is_dir($configDir)) {
            mkdir($configDir, 0777, true);
        }

        $entry = <<<XML
    <service id="{$namespace}\\Subscriber\\MySubscriber">
        <tag name="kernel.event_subscriber"/>
    </service>

XML;

        if (!file_exists($servicesPath)) {
            $xml = <<<XML
<?xml version="1.0" ?>
<container xmlns="http://symfony.com/schema/dic/services"
           xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
           xsi:schemaLocation="http://symfony.com/schema/dic/services
           http://symfony.com/schema/dic/services/services-1.0.xsd">
    <services>
{$entry}    </services>
</container>

XML;
            file_put_contents($servicesPath, $xml);
            return;
        }

        $content = file_get_contents($servicesPath);
        if (strpos($content, $namespace . '\\Subscriber\\MySubscriber') !== false) {
            return;
        }

        $newContent = preg_replace(
            '#</services>#',
            $entry . '    </services>',
            $content
        );

        file_put_contents($servicesPath, $newContent);
    }

    private function confirm(InputInterface $input, OutputInterface $output, string $question): bool
    {
        $helper = $this->getHelper('question');
        return $helper->ask($input, $output, new ConfirmationQuestion($question . ' (y/N) ', false));
    }

    private function getPluginData(InputInterface $input, OutputInterface $output): array
    {
        $bundles = $this->bundleFinder->getAllBundles();

        // Wenn nur ein Plugin gefunden wurde, nimm es automatisch
        if (\count($bundles) === 1) {
            return $bundles[0];
        }

        $choices = array_map(static fn ($bundle) => $bundle['name'], $bundles);
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion('Please select the plugin', $choices);
        $selected = $helper->ask($input, $output, $question);

        foreach ($bundles as $bundle) {
            if ($bundle['name'] === $selected) {
                return $bundle;
            }
        }

        throw new RuntimeException('Selected plugin not found.');
    }
}
