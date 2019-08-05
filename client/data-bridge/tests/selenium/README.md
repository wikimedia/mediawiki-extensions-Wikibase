# Data Bridge browser tests

Currently, these tests assume that your wiki doesn’t care about the domain of the links that bridge is overloading,
and that the wiki is both a Wikibase repo and a client of itself.
This means that you need something like this in your `LocalSettings.php`:

    $wgEnableWikibaseRepo = true;
    $wgEnableWikibaseClient = true;
    $wgWBClientSettings['dataBridgeEnabled'] = true;
    $wgWBClientSettings['dataBridgeHrefRegExp'] = '[/=](?:Item:)?(Q[1-9][0-9]*).*#(P[1-9][0-9]*)$';

The test pages are created in the talk namespace of the main namespace,
so your wiki must allow these talk pages to contain wikitext.
In order to create the test pages and target items,
the test runner needs to log in,
which is usually done by providing a suitable `MEDIAWIKI_USER` and `MEDIAWIKI_PASSWORD` in the environment.
If there is no `WIKIBASE_PROPERTY_STRING` in the environment
(see the general Wikibase selenium README for details),
that user must also have the permissions necessary to create new properties.

You can use the default MediaWiki WDIO configuration,
so a full test command could look somewhat like this
(running from the Wikibase directory, otherwise adjust the paths):

    MW_SERVER=http://localhost MW_SCRIPT_PATH=/wiki/ MEDIAWIKI_USER='...' MEDIAWIKI_PASSWORD='...' node_modules/.bin/wdio ../../tests/selenium/wdio.conf.js --spec client/data-bridge/tests/selenium/specs/

The browser tests are not yet set up in any `package.json`,
nor do they run automatically in CI.
