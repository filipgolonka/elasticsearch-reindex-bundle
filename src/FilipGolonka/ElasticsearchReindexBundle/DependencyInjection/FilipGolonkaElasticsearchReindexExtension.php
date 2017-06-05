<?php

namespace FilipGolonka\ElasticsearchReindexBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class FilipGolonkaElasticsearchReindexExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $container->setAlias(
            'filipgolonka.elasticsearch_reindex.elasticsearch.client',
            $config['elasticsearch_client']
        );

        unset($config['elasticsearch_client']);

        foreach ($config as $name => $value) {
            $container->setParameter(sprintf('filipgolonka.elasticsearch_reindex.param.%s', $name), $value);
        }
    }
}
