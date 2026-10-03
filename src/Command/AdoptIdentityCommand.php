<?php
declare(strict_types=1);
namespace VHUG\SchemaManagerBundle\Command;
use Contao\CoreBundle\Cache\CacheTagManager;
use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use VHUG\SchemaManagerBundle\Schema\EntityIdentity;
use VHUG\SchemaManagerBundle\Model\EntityModel;
#[AsCommand(name: 'schema-manager:adopt-id', description: 'Adopt an established schema identity; defaults to a dry run.')]
final class AdoptIdentityCommand extends Command
{
    public function __construct(private readonly EntityIdentity $identity, private readonly CacheTagManager $tags, private readonly ContaoFramework $framework) { parent::__construct(); }
    protected function configure(): void
    {
        $this->addArgument('record', InputArgument::REQUIRED, 'Schema entity record ID')
            ->addArgument('identity', InputArgument::REQUIRED, 'Established HTTPS entity ID')
            ->addOption('expected-current-id', null, InputOption::VALUE_REQUIRED, 'Required exact current identity')
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Perform the change after reviewing the dry run');
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->framework->initialize();
        $result = $this->identity->adopt((int) $input->getArgument('record'), (string) $input->getOption('expected-current-id'), (string) $input->getArgument('identity'), (bool) $input->getOption('apply'));
        $output->writeln(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), OutputInterface::OUTPUT_RAW);
        if ($result['applied']) { $this->tags->invalidateTagsForModelClass(EntityModel::class); }
        return Command::SUCCESS;
    }
}
