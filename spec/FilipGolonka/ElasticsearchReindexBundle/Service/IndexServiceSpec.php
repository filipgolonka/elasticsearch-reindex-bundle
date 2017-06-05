<?php

namespace spec\FilipGolonka\ElasticsearchReindexBundle\Service;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\BadRequest400Exception;
use Elasticsearch\Namespaces\IndicesNamespace;
use FilipGolonka\ElasticsearchReindexBundle\Config\ElasticSearchConfig;
use FilipGolonka\ElasticsearchReindexBundle\Exception\ElasticSearchException;
use FilipGolonka\ElasticsearchReindexBundle\Service\IndexService;
use FilipGolonka\ElasticsearchReindexBundle\Service\IndexServiceInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

/**
 * @mixin IndexService
 */
class IndexServiceSpec extends ObjectBehavior
{
    const FAILURE_RESPONSE = [
        'acknowledged' => false,
    ];

    const INDEX_NAME = 'indexName';

    const INDEX_TYPE = 'indexType';

    const MAPPING = [];

    const NEW_INDEX_NAME = 'newIndex';

    const OLD_INDEX_NAME = 'oldIndex';

    const SETTINGS = [];

    const SUCCESSFUL_RESPONSE = [
        'acknowledged' => true,
    ];

    private $config;

    function let(Client $client)
    {
        $this->config = ElasticSearchConfig::withNameAndType(self::INDEX_NAME, self::INDEX_TYPE);

        $this->beConstructedWith($client, $this->config);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(IndexService::class);
        $this->shouldImplement(IndexServiceInterface::class);
    }

    function it_removes_index(Client $client, IndicesNamespace $indices)
    {
        $client->indices()->willReturn($indices);

        $indices->delete(Argument::type('array'))->willReturn(self::SUCCESSFUL_RESPONSE);

        $this->removeIndex(self::INDEX_NAME);
    }

    function it_throws_exception_when_can_not_remove_index(Client $client, IndicesNamespace $indices)
    {
        $client->indices()->willReturn($indices);

        $indices->delete(Argument::type('array'))->willReturn(self::FAILURE_RESPONSE);

        $this->shouldThrow(ElasticSearchException::class)->duringRemoveIndex(self::INDEX_NAME);
    }

    function it_creates_index(Client $client, IndicesNamespace $indices)
    {
        $client->indices()->willReturn($indices);

        $indices->create(Argument::type('array'))->willReturn(self::SUCCESSFUL_RESPONSE);

        $this->createIndex(self::INDEX_NAME, self::MAPPING, self::SETTINGS);
    }

    function it_throws_exception_when_can_not_create_index(Client $client, IndicesNamespace $indices)
    {
        $client->indices()->willReturn($indices);

        $indices->create(Argument::type('array'))->willReturn(self::FAILURE_RESPONSE);

        $this
            ->shouldThrow(ElasticSearchException::class)
            ->duringCreateIndex(self::INDEX_NAME, self::MAPPING, self::SETTINGS);
    }

    function it_throws_exception_when_client_throws_exception_during_index_creation(
        Client $client,
        IndicesNamespace $indices
    ) {
        $client->indices()->willReturn($indices);

        $indices->create(Argument::type('array'))->willThrow(new BadRequest400Exception());

        $this
            ->shouldThrow(ElasticSearchException::class)
            ->duringCreateIndex(self::INDEX_NAME, self::MAPPING, self::SETTINGS);
    }


    function it_updates_aliases(Client $client, IndicesNamespace $indices)
    {
        $client->indices()->willReturn($indices);

        $indices->updateAliases(Argument::type('array'))->willReturn(self::SUCCESSFUL_RESPONSE);

        $this->swapIndexAlias(self::OLD_INDEX_NAME, self::NEW_INDEX_NAME);
    }

    function it_throws_exception_when_can_not_swap_aliases(Client $client, IndicesNamespace $indices)
    {
        $client->indices()->willReturn($indices);

        $indices->updateAliases(Argument::type('array'))->willReturn(self::FAILURE_RESPONSE);

        $this
            ->shouldThrow(ElasticSearchException::class)
            ->duringSwapIndexAlias(self::OLD_INDEX_NAME, self::NEW_INDEX_NAME);
    }
}
