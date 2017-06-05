<?php

namespace FilipGolonka\ElasticsearchReindexBundle\Service;

use FilipGolonka\ElasticsearchReindexBundle\Exception\ElasticSearchException;

interface IndexServiceInterface
{
    /**
     * @param string|null $index
     *
     * @throws ElasticSearchException
     */
    public function removeIndex(string $index = null);

    /**
     * @param string $indexNameTemplate
     * @param array $mapping
     * @param array $settings
     *
     * @return string
     * @throws ElasticSearchException
     */
    public function createIndex(string $indexNameTemplate, array $mapping, array $settings): string;

    /**
     * @param string $oldIndex
     * @param string $newIndex
     *
     * @throws ElasticSearchException
     */
    public function swapIndexAlias(string $oldIndex, string $newIndex);
}
