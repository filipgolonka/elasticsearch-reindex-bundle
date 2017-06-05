Feature: Index service
    In order to use bundle
    As a developer
    I want to be able to use index service

    Scenario: Creating index
        Given I create index service
        When I create "new-index" index with:
        """
        mapping={"properties":{"id":{"type": "string"}}}
        settings={"number_of_shards":"1"}
        """
        Then index "new-index" should exists

    Scenario: Removing index
        Given I create index service
        And I create "new-index" index with:
        """
        mapping={"properties":{"id":{"type": "string"}}}
        settings={"number_of_shards":"1"}
        """
        When I remove "new-index" index
        Then index "new-index" should not exists

    Scenario: Swapping aliases
        Given I create index service
        And I remove "index-name" index
        And I create "recent-index" index with:
        """
        mapping={"properties":{"id":{"type": "string"}}}
        settings={"number_of_shards":"1"}
        """
        And I create "more-recent-index" index with:
        """
        mapping={"properties":{"id":{"type": "string"}}}
        settings={"number_of_shards":"1"}
        """
        And I create alias "index-name" to "recent-index" index
        When I swap alias from "recent-index" to "more-recent-index"
        Then alias "index-name" should point to "more-recent-index" index
