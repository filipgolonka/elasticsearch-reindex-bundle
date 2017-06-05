<?php

namespace FilipGolonka\ElasticsearchReindexBundle\Service;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Elasticsearch\Common\Exceptions\ElasticsearchException as BaseElasticSearchException;
use FilipGolonka\ElasticsearchReindexBundle\Config\ElasticSearchConfig;
use FilipGolonka\ElasticsearchReindexBundle\Exception\ElasticSearchException;

class IndexService implements IndexServiceInterface
{
    const INDEX_NAME_TEMPLATE = 'snd-related-%s';

    protected $client;

    protected $config;

    public function __construct(Client $client, ElasticSearchConfig $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     */
    public function removeIndex(string $index = null)
    {
        try {
            $result = $this->client->indices()->delete([
                'index' => $index,
            ]);

            $this->validateResult($result, 'Could not remove index');
        } catch (Missing404Exception $e) {
        }
    }

    /**
     * {@inheritDoc}
     */
    public function createIndex(string $indexNameTemplate, array $mapping, array $settings): string
    {
        $index = sprintf($indexNameTemplate, microtime(true) * 10000);

        try {
            $result = $this->client->indices()->create([
                'index' => $index,
                'body' => [
                    'mappings' => [
                        $this->config->getType() => $mapping,
                    ],
                    'settings' => $settings,
                ],
            ]);

            $this->validateResult($result, 'Could not create index');
        } catch (BaseElasticSearchException $e) {
            throw new ElasticSearchException('Could not create index ' . $e->getMessage(), 0, $e);
        }

        return $index;
    }

    /**
     * {@inheritdoc}
     */
    public function swapIndexAlias(string $oldIndex, string $newIndex)
    {
        $result = $this->client->indices()->updateAliases([
            'body' => [
                'actions' => [
                    [
                        'remove' => [
                            'index' => $oldIndex,
                            'alias' => $this->config->getName(),
                        ],
                    ],
                    [
                        'add' => [
                            'index' => $newIndex,
                            'alias' => $this->config->getName(),
                        ],
                    ],
                ],
            ],
        ]);

        $this->validateResult($result, 'Could not create index alias');
    }

    /**
     * @param array|null $result
     * @param string $message
     *
     * @throws ElasticSearchException
     */
    protected function validateResult($result, string $message)
    {
        $result = $result['acknowledged'] ?? false;

        if (!$result) {
            throw new ElasticSearchException($message);
        }
    }
}
