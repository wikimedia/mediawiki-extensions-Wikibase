# Selenium tests

Please see tests/selenium/README.md file in mediawiki/core repository, usually at mediawiki/vagrant/mediawiki folder.

## Setup

Set up MediaWiki-Vagrant:

    cd mediawiki/vagrant
    vagrant up
    vagrant roles enable wikidata
    vagrant provision
    cd mediawiki
    npm install

## Run all specs

Run test specs from both mediawiki/core and installed extensions:

    cd mediawiki
    npm run selenium

## Run specific tests

To run only some tests, you first have to start Chromedriver in one terminal window:

    chromedriver --url-base=wd/hub --port=4444

Then, in another terminal window run this the current extension directory:

    npm install
    npm run selenium-test -- --spec tests/selenium/specs/FILE-NAME.js

You can also filter specific test(s) by name:

    npm run selenium-test -- --spec tests/selenium/specs/FILE-NAME.js --mochaOpts.grep TEST-NAME

Make sure Chromedriver is running when executing the above command.

## Environment

The behavior of the tests can be modified with several environment variables.

* `MW_SERVER`: protocol, host name and port of the MediaWiki installation.
  Defaults to `http://127.0.0.1:8080` (Vagrant).
* `MW_SCRIPT_PATH`: path to `index.php`, `api.php` etc. under `MW_SERVER`.
  Defaults to `/w`.
* `LOG_DIR`: Directory to leave logs and screenshots in.
  Defaults to a `log/` subdirectory of this directory.
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
