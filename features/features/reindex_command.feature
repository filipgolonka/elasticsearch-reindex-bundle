Feature: Reindex command
    In order to use bundle
    As a developer
    I want to be able to run reindex command

    Scenario: Launching reindex command
        Given I create container with:
        """
        filipgolonka.elasticsearch_reindex.param.index_name_template=new-index-name
        filipgolonka.elasticsearch_reindex.param.mapping={"properties":{"id":{"type": "string"}}}
        filipgolonka.elasticsearch_reindex.param.settings={"number_of_shards":"1"}
        filipgolonka.elasticsearch_reindex.param.reindex_command_name=dummy.command
        """
        And I add dummy command as "dummy.command" to container
        Given I create index service
        And I create setting service
        And I remove "index-name" index
        And I create "recent-index" index with:
        """
        mapping={"dynamic": "environment == 'dev' ? 'strict' : false", "properties":{"id":{"type": "string"}}}
        settings={"number_of_shards":"1"}
        """
        And I create alias "index-name" to "recent-index" index
        And I set "CURRENT_INDEX_NAME" setting with value "recent-index"
        When I launch reindex command
        Then alias "index-name" should point to "new-index-name" index
