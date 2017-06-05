<?php

namespace FilipGolonka\ElasticsearchReindexBundle;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DummyCommand extends Command
{
    public function configure()
    {
        $this
            ->setName('dummy-command')
            ->addArgument('command')
            ->addArgument('index_name')
        ;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
    }
}
