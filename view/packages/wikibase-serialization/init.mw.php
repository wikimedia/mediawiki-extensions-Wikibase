<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

$GLOBALS['wgExtensionCredits']['wikibase'][] = array(
	'path' => __FILE__,
	'name' => 'Wikibase Serialization JavaScript',
	'version' => '2.0-alpha',
	'author' => array(
		'[http://www.snater.com H. Snater]',
	),
	'url' => 'https://github.com/wmde/WikibaseSerializationJavaScript',
	'description' => 'JavaScript library containing serializers and deserializers for the Wikibase DataModel.',
	'license-name' => 'GPL-2.0+',
);

include 'resources.php';
include 'resources.tests.php';
