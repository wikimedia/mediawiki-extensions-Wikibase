<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

$GLOBALS['wgExtensionCredits']['wikibase'][] = [
	'path' => __FILE__,
	'name' => 'Wikibase Serialization JavaScript',
	'version' => WIKIBASE_SERIALIZATION_JAVASCRIPT_VERSION,
	'author' => [
		'[http://www.snater.com H. Snater]',
	],
	'url' => 'https://github.com/wmde/WikibaseSerializationJavaScript',
	'description' => 'JavaScript library containing serializers and deserializers for the Wikibase DataModel.',
	'license-name' => 'GPL-2.0+',
];

include __DIR__ . '/resources.php';
include __DIR__ . '/resources.tests.php';
