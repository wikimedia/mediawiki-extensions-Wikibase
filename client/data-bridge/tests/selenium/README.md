# Data Bridge browser tests

Currently, these tests assume that your wiki doesn’t care about the domain of the links that bridge is overloading,
that the wiki is both a Wikibase repo and a client of itself,
and that it tags edits with “Data Bridge”.
This means that you need something like this (be sure to check `repo/config/` & `client/config/` for an exhaustive list) in your `LocalSettings.php`:

    $wgEnableWikibaseRepo = true;
    $wgEnableWikibaseClient = true;
    $wgWBClientSettings['dataBridgeEnabled'] = true;
    $wgWBClientSettings['dataBridgeHrefRegExp'] = '[/=]((?:Item:)?(Q[1-9][0-9]*)).*#(P[1-9][0-9]*)$';
    $wgWBClientSettings['dataBridgeEditTags'] = [ 'Data Bridge' ];
    $wgWBRepoSettings['dataBridgeEnabled'] = true;

The test pages are created in the talk namespace of the main namespace,
so your wiki must allow these talk pages to contain wikitext.
In order to create the test pages and target items,
the test runner needs to log in,
which is usually done by providing a suitable `MEDIAWIKI_USER` and `MEDIAWIKI_PASSWORD` in the environment.
If there is no `WIKIBASE_PROPERTY_STRING` in the environment
(see the general Wikibase selenium README for details),
that user must also have the permissions necessary to create new properties;
similarly, if the “Data Bridge” does not exist,
that user must also have the permissions necessary to create new tags.

# Selenium tests

For more information see https://www.mediawiki.org/wiki/Selenium

## Setup

See https://www.mediawiki.org/wiki/MediaWiki-Docker/Extension/Wikibase

## Run all tests

    npm run selenium-test

## Run specific tests

Just tests from this folder (client/data-bridge/tests/selenium)

    npm run selenium-test:bridge

Filter by file name:

    npm run selenium-test -- --spec tests/selenium/specs/[FILE-NAME]

Filter by file name and test name:

    npm run selenium-test -- --spec tests/selenium/specs/[FILE-NAME] --mochaOpts.grep [TEST-NAME]
