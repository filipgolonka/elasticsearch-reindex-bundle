<?php

namespace FilipGolonka\ElasticsearchReindexBundle\Service;

use FilipGolonka\ElasticsearchReindexBundle\Exception\ElasticSearchException;

interface SettingServiceInterface
{
    const CURRENT_INDEX_NAME = 'CURRENT_INDEX_NAME';

    const NEXT_INDEX_NAME = 'NEXT_INDEX_NAME';

    /**
     * @param string $key
     *
     * @return string
     * @throws ElasticSearchException
     */
    public function getSetting(string $key): string;

    /**
     * @param string $key
     * @param string $value
     *
     * @throws ElasticSearchException
     */
    public function setSetting(string $key, string $value);
}
