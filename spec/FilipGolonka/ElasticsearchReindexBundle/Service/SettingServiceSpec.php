<?php

namespace spec\FilipGolonka\ElasticsearchReindexBundle\Service;

use Elasticsearch\Client;
use FilipGolonka\ElasticsearchReindexBundle\Config\ElasticSearchConfig;
use FilipGolonka\ElasticsearchReindexBundle\Exception\ElasticSearchException;
use FilipGolonka\ElasticsearchReindexBundle\Service\SettingService;
use FilipGolonka\ElasticsearchReindexBundle\Service\SettingServiceInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

/**
 * @mixin SettingService
 */
class SettingServiceSpec extends ObjectBehavior
{
    const INDEX_NAME = 'indexName';

    const INDEX_TYPE = 'indexType';

    const SETTING_NAME = 'settingName';

    const SETTING_VALUE = 'settingValue';

    private $config;

    function let(Client $client)
    {
        $this->config = ElasticSearchConfig::withNameAndType(self::INDEX_NAME, self::INDEX_TYPE);

        $this->beConstructedWith($client, $this->config);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(SettingService::class);
        $this->shouldImplement(SettingServiceInterface::class);
    }

    function it_gets_setting(Client $client)
    {
        $client->get(Argument::type('array'))->willReturn([
            '_source' => [
                'value' => self::SETTING_VALUE,
            ],
        ]);

        $this->getSetting(self::SETTING_NAME)->shouldReturn(self::SETTING_VALUE);
    }

    function it_throws_exception_when_setting_does_not_exist()
    {
        $this->shouldThrow(ElasticSearchException::class)->duringGetSetting(self::SETTING_VALUE);
    }

    function it_sets_setting(Client $client)
    {
        $client->index(Argument::type('array'))->willReturn([]);

        $this->setSetting(self::SETTING_NAME, self::SETTING_VALUE);
    }

    function it_throws_exception_when_setting_can_not_be_set(Client $client)
    {
        $client->index(Argument::type('array'))->willReturn([
            '_shards' => [
                'failed' => true,
            ],
        ]);

        $this->shouldThrow(ElasticSearchException::class)->duringSetSetting(self::SETTING_NAME, self::SETTING_VALUE);
    }
}
