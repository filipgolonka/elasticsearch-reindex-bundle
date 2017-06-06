<?php

namespace FilipGolonka\ElasticsearchReindexBundle;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Exception;
use FilipGolonka\ElasticsearchReindexBundle\Command\ReindexCommand;
use FilipGolonka\ElasticsearchReindexBundle\Config\ElasticSearchConfig;
use FilipGolonka\ElasticsearchReindexBundle\Service\IndexService;
use FilipGolonka\ElasticsearchReindexBundle\Service\IndexServiceInterface;
use FilipGolonka\ElasticsearchReindexBundle\Service\SettingService;
use FilipGolonka\ElasticsearchReindexBundle\Service\SettingServiceInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FeatureContext implements Context
{
    const INDEX_NAME = 'index-name';

    const INDEX_TYPE = 'index-type';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var ElasticSearchConfig
     */
    private $config;

    /**
     * @var array
     */
    private $createdIndices = [];

    /**
     * @var SettingServiceInterface
     */
    private $settingService;

    /**
     * @var IndexServiceInterface
     */
    private $indexService;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @BeforeScenario
     */
    public function setUp()
    {
        $elasticsearchUrl = getenv('ELASTICSEARCH_URL');
        if (!$elasticsearchUrl) {
            throw new Exception(
                'You have to provide elasticsearch url, for example: export ELASTICSEARCH_URL=http://localhost:9200'
            );
        }

        $clientBuilder = ClientBuilder::create();
        $clientBuilder->setHosts([
            $elasticsearchUrl,
        ]);

        $this->client = $clientBuilder->build();

        $this->config = ElasticSearchConfig::withNameAndType(self::INDEX_NAME, self::INDEX_TYPE);
    }

    /**
     * @AfterScenario
     */
    public function cleanUp()
    {
        $indexService = new IndexService($this->client, $this->config);
        foreach (array_unique($this->createdIndices) as $indexName) {
            $indexService->removeIndex($indexName);
        }
    }

    /**
     * @Given I create setting service
     */
    public function iCreateSettingService()
    {
        $this->settingService = new SettingService($this->client, $this->config);
    }

    /**
     * @When I set :settingName setting with value :settingValue
     */
    public function iSetSettingWithValue(string $settingName, string $settingValue)
    {
        $this->settingService->setSetting($settingName, $settingValue);

        $this->createdIndices[] = self::INDEX_NAME;
    }

    /**
     * @Then setting :settingName should have :settingValue value
     */
    public function settingShouldHaveValue(string $settingName, string $settingValue)
    {
        $setting = $this->settingService->getSetting($settingName);
        if ($settingValue != $setting) {
            throw new Exception(sprintf('Expected setting to be "%s", actual "%s"', $settingValue, $setting));
        }
    }

    /**
     * @Given I create index service
     */
    public function iCreateIndexService()
    {
        $this->indexService = new IndexService($this->client, $this->config);
    }

    /**
     * @When I create :indexName index with:
     */
    public function iCreateIndexWithType(string $indexName, PyStringNode $config)
    {
        $mapping = null;
        $settings = null;

        foreach ($config->getStrings() as $line) {
            $line = explode('=', $line);
            $key = array_shift($line);
            $value = implode('=', $line);

            if ($key == 'mapping') {
                $mapping = json_decode($value, true);
            } elseif ($key == 'settings') {
                $settings = json_decode($value, true);
            }
        }

        if (is_null($mapping) || is_null($settings)) {
            throw new Exception('Missing index mapping and/or settings');
        }

        $this->indexService->createIndex($indexName, $mapping, $settings);

        $this->createdIndices[] = $indexName;
    }

    /**
     * @Then index :indexName should exists
     */
    public function indexShouldExists(string $indexName)
    {
        if (!$this->client->indices()->exists(['index' => $indexName])) {
            throw new Exception(sprintf('Index "%s" should exist', $indexName));
        }
    }

    /**
     * @When I remove :indexName index
     */
    public function iRemoveIndex(string $indexName)
    {
        $this->indexService->removeIndex($indexName);
    }

    /**
     * @Then index :indexName should not exists
     */
    public function indexShouldNotExists(string $indexName)
    {
        if ($this->client->indices()->exists(['index' => $indexName])) {
            throw new Exception(sprintf('Index "%s" should not exist', $indexName));
        }
    }

    /**
     * @Given I create alias :aliasName to :indexName index
     */
    public function iCreateAliasToIndex(string $aliasName, string $indexName)
    {
        $this->client->indices()->updateAliases([
            'body' => [
                'actions' => [
                    [
                        'add' => [
                            'index' => $indexName,
                            'alias' => $aliasName,
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @When I swap alias from :oldIndex to :newIndex
     */
    public function iSwapAliasFromTo(string $oldIndex, string $newIndex)
    {
        $this->indexService->swapIndexAlias($oldIndex, $newIndex);
    }

    /**
     * @Then alias :aliasName should point to :indexName index
     */
    public function aliasShouldPointToIndex(string $aliasName, string $indexName)
    {
        $aliasInfo = $this->client->indices()->get(['index' => $aliasName]);
        $indicesPointsToAlias = array_keys($aliasInfo);

        if (!in_array($indexName, $indicesPointsToAlias)) {
            throw new Exception(
                sprintf(
                    'Index "%s" should point to alias "%s", but it does not. Indices point to alias: "%s"',
                    $indexName,
                    $aliasName,
                    implode(', ', $indicesPointsToAlias)
                )
            );
        }
    }

    /**
     * @Given I create container with:
     */
    public function iCreateContainerWith(PyStringNode $config)
    {
        $this->container = new Container();

        foreach ($config->getStrings() as $line) {
            $line = explode('=', $line);

            $value = json_decode($line[1], true);
            if ($value === null) {
                $value = $line[1];
            }

            $this->container->setParameter($line[0], $value);
        }
    }

    /**
     * @Given I add dummy command as :serviceName to container
     */
    public function iAddDummyCommandAsToContainer(string $serviceName)
    {
        $this->container->set($serviceName, new DummyCommand());
    }

    /**
     * @When I launch reindex command
     */
    public function iLaunchReindexCommand()
    {
        $command = new ReindexCommand($this->indexService, $this->settingService, 'environment');
        $command->setContainer($this->container);

        $input = new ArrayInput([]);

        $command->run($input, new NullOutput());
    }
}
