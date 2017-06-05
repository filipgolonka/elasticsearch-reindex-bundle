<?php

namespace FilipGolonka\ElasticsearchReindexBundle\Service;

use Elasticsearch\Client;
use FilipGolonka\ElasticsearchReindexBundle\Config\ElasticSearchConfig;
use FilipGolonka\ElasticsearchReindexBundle\Exception\ElasticSearchException;

class SettingService implements SettingServiceInterface
{
    const DEFAULT_TYPE = 'settings';

    protected $client;

    protected $config;

    public function __construct(Client $client, ElasticSearchConfig $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getSetting(string $key): string
    {
        $result = $this->client->get([
            'index' => $this->config->getName(),
            'type' => self::DEFAULT_TYPE,
            'id' => $key,
        ]);

        if (!isset($result['_source']['value'])) {
            throw new ElasticSearchException(sprintf('Setting "%s" does not exist.', $key));
        }

        return $result['_source']['value'];
    }

    /**
     * {@inheritdoc}
     */
    public function setSetting(string $key, string $value)
    {
        $response = $this->client->index([
            'index' => $this->config->getName(),
            'type' => self::DEFAULT_TYPE,
            'id' => $key,
            'body' => [
                'value' => $value,
            ],
        ]);

        if (!empty($response['_shards']['failed'])) {
            throw new ElasticSearchException(sprintf('Could not update setting "%s"', $key));
        }
    }
}
