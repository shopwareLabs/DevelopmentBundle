<?php

declare(strict_types=1);

namespace Shopware\Development\Make\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'dev:make:plugin:entity',
    description: 'Generates a new DAL entity for a Shopware plugin or bundle',
)]
class MakeEntityCommand extends AbstractMakeCommand
{
    private const MAX_ENTITY_NAME_LENGTH = 50;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Shopware Entity Generator');

        $pluginPath = $this->bundleFinder->askForBundle($io);
        $entityName = $this->askForEntityName($io);
        $entityBaseName = rtrim($entityName, 'Entity');
        $entityTable = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $entityBaseName));
        $defaultNamespacePath = 'Core/Content/' . $entityBaseName;

        $entityConfig = $this->namespacePickerService->pickNamespace($io, $pluginPath, $defaultNamespacePath);
        $basePath = $entityConfig['path'];

        $variables = [
            'NAMESPACE' => $entityConfig['namespace'],
            'ENTITY_NAME' => $entityBaseName,
            'ENTITY_TABLE' => $entityTable,
        ];

        $this->generateContent($io, 'entity/entity-definition.template', $variables, $basePath . '/' . $entityBaseName . 'Definition.php');
        $this->generateContent($io, 'entity/entity-entity.template', $variables, $basePath . '/' . $entityBaseName . 'Entity.php');
        $this->generateContent($io, 'entity/entity-collection.template', $variables, $basePath . '/' . $entityBaseName . 'Collection.php');

        if ($io->confirm('Do you want to generate a matching migration for this entity?', true)) {
            $this->createMigration($io, $pluginPath, $entityBaseName, $entityTable);
        }

        $io->success("Entity $entityName generated successfully in: $basePath");

        return self::SUCCESS;
    }

    private function askForEntityName(SymfonyStyle $io): string
    {
        return $io->ask(
            'Name of the Entity (e.g. CustomThing)',
            null,
            function ($value) {
                $this->validateEntityName($value);
                return $value;
            }
        );
    }

    private function createMigration(SymfonyStyle $io, array $pluginPath, string $entityBaseName, string $entityTable): void
    {
        $timestamp = (new \DateTimeImmutable())->format('YmdHis');
        $migrationClass = 'Migration' . $timestamp . $entityBaseName;
        $migrationNamespace = $pluginPath['namespace'] . '\\Migration';
        $migrationDir = $pluginPath['path'] . '/Migration';
        $migrationPath = $migrationDir . '/' . $migrationClass . '.php';

        $migrationVariables = [
            'MIGRATION_NAMESPACE' => $migrationNamespace,
            'MIGRATION_CLASS' => $migrationClass,
            'MIGRATION_TIMESTAMP' => $timestamp,
            'ENTITY_TABLE' => $entityTable,
            'ENTITY_NAME' => $entityBaseName,
        ];

        $this->generateContent($io, 'migration/create-entity-table.template', $migrationVariables, $migrationPath);
        $io->success("Migration created at: $migrationPath");
    }

    private function validateEntityName(string $name): void
    {
        $trimmed = trim($name);

        if (empty($trimmed)) {
            throw new \RuntimeException('Entity name cannot be empty.');
        }

        if (!preg_match('/^[A-Z][a-zA-Z0-9]*$/', $trimmed)) {
            throw new \RuntimeException(
                'Entity name must start with an uppercase letter and contain only alphanumeric characters.'
            );
        }

        if (strlen($trimmed) > self::MAX_ENTITY_NAME_LENGTH) {
            throw new \RuntimeException(
                sprintf('Entity name cannot be longer than %d characters.', self::MAX_ENTITY_NAME_LENGTH)
            );
        }
    }
}
