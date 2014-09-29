<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

$GLOBALS['wgExtensionCredits']['wikibase'][] = array(
	'path' => __FILE__,
	'name' => 'Wikibase Serialization JavaScript',
	'version' => '1.1.3',
	'author' => array(
		'[https://www.mediawiki.org/wiki/User:Danwe Daniel Werner]',
		'[http://www.snater.com H. Snater]',
	),
	'url' => 'https://github.com/wmde/WikibaseSerializationJavaScript',
	'description' => 'JavaScript library containing serializers and deserializers for the Wikibase DataModel.',
	'license-name' => 'GPL-2.0+'
);

include 'resources.mw.php';
include 'resources.test.mw.php';
