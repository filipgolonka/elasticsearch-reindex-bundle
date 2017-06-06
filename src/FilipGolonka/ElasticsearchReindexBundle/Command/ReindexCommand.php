<?php

namespace FilipGolonka\ElasticsearchReindexBundle\Command;

use FilipGolonka\ElasticsearchReindexBundle\Service\IndexServiceInterface;
use FilipGolonka\ElasticsearchReindexBundle\Service\SettingServiceInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ReindexCommand extends ContainerAwareCommand
{
    const COMMAND_NAME = 'filipgolonka:elasticsearch:reindex';

    const COMMAND_DESCRIPTION = 'Reindex Elasticsearch data';

    protected $indexService;

    protected $settingService;

    protected $environment;

    public function __construct(
        IndexServiceInterface $indexService,
        SettingServiceInterface $settingService,
        string $environment
    ) {
        parent::__construct();

        $this->indexService = $indexService;
        $this->settingService = $settingService;
        $this->environment = $environment;
    }

    protected function configure()
    {
        $this
            ->setName(static::COMMAND_NAME)
            ->setDescription(static::COMMAND_DESCRIPTION);
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

        $newIndex = $this->createNewIndex($this->environment);
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

    private function createNewIndex(string $environment): string
    {
        $container = $this->getContainer();
        $indexNameTemplate = $container->getParameter('filipgolonka.elasticsearch_reindex.param.index_name_template');

        $mapping = $container->getParameter('filipgolonka.elasticsearch_reindex.param.mapping');
        $mapping = $this->evaluateDynamicMapping($mapping, $environment);

        $settings = $container->getParameter('filipgolonka.elasticsearch_reindex.param.settings');

        $newIndex = $this->indexService->createIndex($indexNameTemplate, $mapping, $settings);

        return $newIndex;
    }

    private function evaluateDynamicMapping(array $mapping, string $environment): array
    {
        if (isset($mapping['dynamic'])) {
            $language = new ExpressionLanguage();

            $mapping['dynamic'] = $language->evaluate(
                $mapping['dynamic'],
                [
                    'environment' => $environment,
                ]
            );
        }

        return $mapping;
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
