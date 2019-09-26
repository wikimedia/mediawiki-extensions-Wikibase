# Data Bridge browser tests

Currently, these tests assume that your wiki doesn’t care about the domain of the links that bridge is overloading,
that the wiki is both a Wikibase repo and a client of itself,
and that it tags edits with “Data Bridge”.
This means that you need something like this in your `LocalSettings.php`:

    $wgEnableWikibaseRepo = true;
    $wgEnableWikibaseClient = true;
    $wgWBClientSettings['dataBridgeEnabled'] = true;
    $wgWBClientSettings['dataBridgeHrefRegExp'] = '[/=](?:Item:)?(Q[1-9][0-9]*).*#(P[1-9][0-9]*)$';
    $wgWBClientSettings['dataBridgeEditTags'] = [ 'Data Bridge' ];

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

Data Bridge is already using the current version 5 of webdriverio,
while Mediawiki core and Wikibase repo still need migration to that version.

A complete test command run from the data-bridge directory could be:

    MW_SERVER=http://localhost MW_SCRIPT_PATH=/wiki/ MEDIAWIKI_USER='...' MEDIAWIKI_PASSWORD='...' npm run selenium-test
