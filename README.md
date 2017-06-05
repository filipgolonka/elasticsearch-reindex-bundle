# Elasticsearch reindex bundle

Allows to reindex data in zero-downtime mode. 

![https://travis-ci.org/filipgolonka/elasticsearch-reindex-bundle](https://travis-ci.org/filipgolonka/elasticsearch-reindex-bundle.svg?branch=master)

## How is it working?

See [https://www.elastic.co/blog/changing-mapping-with-zero-downtime](https://www.elastic.co/blog/changing-mapping-with-zero-downtime)

Long story short:
* create new index
* fill in with data
* point an alias to new index
* remove old index

Your application has to use alias name, not index name directly.

## Requirements

* elasticsearch/elasticsearch >=2.0, <5.0
* symfony/config
* PHP >7.0

## Installation

* `composer require filipgolonka/elasticsearch-reindex-bundle`
* add following lines to `app/AppKernel.php`

```
$bundles = [
    // ...
    new FilipGolonka\ElasticsearchReindexBundle\FilipGolonkaElasticsearchReindexBundle(),
    // ...
];
```

* add config options to `app/config/config.yml`

```
filip_golonka_elasticsearch_reindex:
    alias_name: 'alias_name'
    elasticsearch_client: 'elasticsearch.client'
    index_name_template: 'my-index-%s'
    index_type: my-type
    mapping: '%elasticsearch_mapping%'
    reindex_command_name: 'app.command.elastic_search.importer.all_to'
    settings: '%elasticsearch_settings%'
```
where:
`alias_name` - alias used by your application
`elasticsearch_client` - DI container id, which points to `\Elasticsearch\Client` object
`index_name_template` - template used for creating new index. It has to be sprintf pattern with one placement (in the other words - contains one and only one `%s`)
`index_type` - document types used in your application
`mapping` - Elasticsearch mapping for newly created index
`reindex_command_name` - DI container id, which points to `\Symfony\Component\Console\Command\Command` object. This command has to accept one and only one argument: `index_name`.
`settings` - Elasticsearch settings for newly created index

## Elasticsearch mapping and settings example

```
parameters:
    elasticsearch_mapping:
        properties:
            id:
                type: string
            date:
                type: date
            gallery:
                type: object
                properties:
                    image:
                        type: string
    
    elasticsearch_settings:
        number_of_shards: 1,
        number_of_replicas: 0,
        analysis:
            analyzer:
                analyzer_keyword:
                    tokenizer: 'keyword'
                    filter: 'lowercase'
```

## Development

Add your functionality, but before submitting Pull Request - make sure, that you didn't break the quality of code.

First - run code static analyses tools:
```
bin/phing quality
```

Second - run tests:
```
bin/phing tests
```

Before submitting Pull Request, make sure that your code is covered with PhpSpec and Behat tests.

### How to run Behat locally?

Behat tests if the bundle logic works properly with connection to Elasticsearch, so Elasticsearch url is needed to run tests.
How to do it?

```
export ELASTICSEARCH_URL=http://localhost:9200 && bin/behat
```
