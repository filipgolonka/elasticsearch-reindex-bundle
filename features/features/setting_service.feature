Feature: Setting service
    In order to use bundle
    As a developer
    I want to be able to store index setting

    Scenario: Using setting service
        Given I create setting service
        When I set "SETTING_NAME" setting with value "SETTING_VALUE"
        Then setting "SETTING_NAME" should have "SETTING_VALUE" value
