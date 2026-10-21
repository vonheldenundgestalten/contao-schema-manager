<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface,InputArgument,InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use VHUG\SchemaManagerBundle\Extension\Internal\PayloadMigrator;
#[AsCommand(name:'schema-manager:extension:migrate',description:'Preview or explicitly apply a registered supplementary payload migration.')]
final class MigrateExtensionCommand extends Command
{
    public function __construct(private readonly PayloadMigrator $migrator){parent::__construct();}
    protected function configure(): void { $this->addArgument('entity',InputArgument::REQUIRED,'Managed entity record ID')->addOption('apply',null,InputOption::VALUE_NONE,'Apply after reviewing the dry run'); }
    protected function execute(InputInterface $input,OutputInterface $output): int
    {
        $id=filter_var($input->getArgument('entity'),FILTER_VALIDATE_INT);
        if(!$id || $id<1)throw new \InvalidArgumentException('Valid entity ID required.');
        $output->writeln(json_encode($this->migrator->migrate($id,(bool)$input->getOption('apply')),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
        return Command::SUCCESS;
    }
}
