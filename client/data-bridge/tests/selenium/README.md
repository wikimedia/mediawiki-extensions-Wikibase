# Data Bridge browser tests

Currently, these tests assume that your wiki is set up as a client of Beta Wikidata,
which means that you need something like this in your `LocalSettings.php`:

    $wgWBClientSettings['dataBridgeHrefRegExp'] = '^https://wikidata\.beta\.wmflabs\.org/wiki/Item:(Q[1-9][0-9]*).*#(P[1-9][0-9]*)$';
    $wgWBClientSettings['repoUrl'] = 'https://wikidata.beta.wmflabs.org';
    $wgWBClientSettings['repoScriptPath'] = '/w';
    $wgWBClientSettings['repoArticlePath'] = '/wiki/$1';

No edits will be made on Beta Wikidata,
but a test page will be created on the local wiki each time you run the tests,
with some data bridge links to Beta Wikidata.
The test pages are created in the main namespace,
which must support wikitext pages
(i. e., if your wiki is also a Wikibase repository, it must have a separate Item namespace);
in order to create them, the test runner needs to log in,
which is usually done by providing a suitable `MEDIAWIKI_USER` and `MEDIAWIKI_PASSWORD` in the environment.

You can use the default MediaWiki WDIO configuration,
so a full test command could look somewhat like this
(running from the Wikibase directory, otherwise adjust the paths):

    MW_SERVER=http://localhost MW_SCRIPT_PATH=/wiki/ MEDIAWIKI_USER='...' MEDIAWIKI_PASSWORD='...' node_modules/.bin/wdio ../../tests/selenium/wdio.conf.js --spec client/data-bridge/tests/selenium/specs/

The browser tests are not yet set up in any `package.json`,
nor do they run automatically in CI.
