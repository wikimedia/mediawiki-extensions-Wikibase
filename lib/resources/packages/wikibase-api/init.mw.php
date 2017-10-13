<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

$GLOBALS['wgExtensionCredits']['wikibase'][] = [
	'path' => __FILE__,
	'name' => 'Wikibase JavaScript API',
	'version' => WIKIBASE_JAVASCRIPT_API_VERSION,
	'author' => [
		'[http://www.snater.com H. Snater]',
	],
	'url' => 'https://git.wikimedia.org/summary/mediawiki%2Fextensions%2FWikibaseJavaScriptApi',
	'description' => 'Wikibase API client in JavaScript',
	'license-name' => 'GPL-2.0+'
];
