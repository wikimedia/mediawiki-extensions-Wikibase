# Selenium tests

For more information see https://www.mediawiki.org/wiki/Selenium

## Setup

See https://www.mediawiki.org/wiki/MediaWiki-Docker/Extension/Wikibase

## Run all tests

    npm run selenium-test

## Run specific tests

Just tests from this folder (repo/tests/selenium)

    npm run selenium-test:repo

Filter by file name:

    npm run selenium-test -- --spec tests/selenium/specs/[FILE-NAME]

Filter by file name and test name:

    npm run selenium-test -- --spec tests/selenium/specs/[FILE-NAME] --mochaOpts.grep [TEST-NAME]

## Environment

The behavior of the tests can be modified with several environment variables.

* `WIKIBASE_PROPERTY_STRING`, `WIKIBASE_PROPERTY_URL`, etc.:
  Property ID of a property with datatype `string`, `url`, etc. –
  if not set, a new property of this type will be created each time the tests are run.
  (This will fail unless anonymous users are allowed to create properties on the wiki,
  so setting `WIKIBASE_PROPERTY_STRING` correctly is recommended.)

## Write more tests

When working on the browser tests,
you’ll want to consult the documentation of the following libraries we use:

* [WebdriverIO](http://webdriver.io/api.html) for controlling the browser
  (`browser`, `$`, `waitForVisible`, …)
* [Mocha](https://mochajs.org/) as the general testing framework
  (`describe`, `it`, `before`, …)
* [`assert`](https://nodejs.org/api/assert.html) for simple assertions
  (`ok`, `strictEqual`, …)
