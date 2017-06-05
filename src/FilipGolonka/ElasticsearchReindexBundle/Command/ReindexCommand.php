<?php

namespace FilipGolonka\ElasticsearchReindexBundle\Command;

use FilipGolonka\ElasticsearchReindexBundle\Service\IndexServiceInterface;
use FilipGolonka\ElasticsearchReindexBundle\Service\SettingServiceInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReindexCommand extends ContainerAwareCommand
{
    const COMMAND_NAME = 'filipgolonka:elasticsearch:reindex';

    const COMMAND_DESCRIPTION = 'Reindex Elasticsearch data';

    protected $indexService;

    protected $settingService;

    public function __construct(
        IndexServiceInterface $indexService,
        SettingServiceInterface $settingService
    ) {
        parent::__construct();

        $this->indexService = $indexService;
        $this->settingService = $settingService;
    }

    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_DESCRIPTION);
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.UnusedLocalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $currentIndex = $this->settingService->getSetting(SettingServiceInterface::CURRENT_INDEX_NAME);

        $output->writeln(sprintf('Current index name is "%s"', $currentIndex), OutputInterface::VERBOSITY_VERBOSE);

        $container = $this->getContainer();
        $indexNameTemplate = $container->getParameter('filipgolonka.elasticsearch_reindex.param.index_name_template');
        $mapping = $container->getParameter('filipgolonka.elasticsearch_reindex.param.mapping');
        $settings = $container->getParameter('filipgolonka.elasticsearch_reindex.param.settings');

        $newIndex = $this->indexService->createIndex($indexNameTemplate, $mapping, $settings);

        $output->writeln(sprintf('New index created, name is "%s"', $newIndex), OutputInterface::VERBOSITY_VERBOSE);

        $output->writeln('Feeding new index with data...');

        $this->reindexDataTo($newIndex, $output);

        $output->writeln('Swapping index aliases...', OutputInterface::VERBOSITY_VERBOSE);

        $this->indexService->swapIndexAlias($currentIndex, $newIndex);

        $output->writeln(sprintf('Saving new index name - "%s"', $newIndex), OutputInterface::VERBOSITY_VERBOSE);

        $this->settingService->setSetting(SettingServiceInterface::CURRENT_INDEX_NAME, $newIndex);

        $output->writeln(sprintf('Removing old index - "%s"', $currentIndex), OutputInterface::VERBOSITY_VERBOSE);

        $this->indexService->removeIndex($currentIndex);

        $output->writeln('Done!');

        return 0;
    }

    private function reindexDataTo(string $newIndex, OutputInterface $output): int
    {
        $container = $this->getContainer();

        $reindexCommandName = $container->getParameter('filipgolonka.elasticsearch_reindex.param.reindex_command_name');

        $command = $container->get($reindexCommandName);

        $arguments = [
            'command' => $reindexCommandName,
            'index_name' => $newIndex,
        ];

        $greetInput = new ArrayInput($arguments);
        return $command->run($greetInput, $output);
    }
}
