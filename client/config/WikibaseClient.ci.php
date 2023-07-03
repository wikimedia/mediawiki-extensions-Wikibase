<?php

/**
 * CI configuration for the Wikibase Client extension.
 *
 * Largely uses the default config for testing,
 * but adds settings that are not part of the default config yet,
 * and also configures an example Repo for testing Client without Repo enabled.
 *
 * This file is NOT an entry point the Wikibase Client extension. Use this instead:
 * wfLoadExtension( 'WikibaseClient', "$IP/extensions/Wikibase/extension-client.json" )
 * It should furthermore not be included from outside the extension.
 *
 * @see docs/options.wiki
 *
 * @license GPL-2.0-or-later
 */

// Make this explicitly global for the PHPUnit bootstrap code. This is only necessary
// because ExtensionLoadHandler tries to read the global before the bootstrap code
// actually put these values into the global scope.
global $wgWBClientSettings;

// enable data access in user language, for LuaWikibaseIntegrationTest
$wgWBClientSettings['allowDataAccessInUserLanguage'] = true;

// enable Data Bridge (Wikidata Bridge)
$wgWBClientSettings['dataBridgeEnabled'] = true;
$wgWBClientSettings['dataBridgeHrefRegExp'] = '[/=]((?:Item:)?(Q[1-9][0-9]*)).*#(P[1-9][0-9]*)$';
$wgWBClientSettings['dataBridgeEditTags'] = [ 'Data Bridge' ];

// Reduce injecting RC records batch size (T299077)
$wgWBClientSettings['recentChangesBatchSize'] = 10;

// if this is a Client-only wiki, configure a fake Repo
if ( !( $wgEnableWikibaseRepo ?? true ) ) {
	$wgWBClientSettings['repoUrl'] = 'https://ci.wikibase.example';
	$wgWBClientSettings['entitySources'] = [
		'local' => [
			'repoDatabase' => 'repo',
			'baseUri' => $wgWBClientSettings['repoUrl'] . '/entity',
			'entityNamespaces' => [
				'item' => 120,
				'property' => 122,
			],
			'rdfNodeNamespacePrefix' => 'wd',
			'rdfPredicateNamespacePrefix' => '',
			'interwikiPrefix' => '',
		],
	];
}
