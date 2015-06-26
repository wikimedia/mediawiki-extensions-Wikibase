<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

$GLOBALS['wgExtensionCredits']['wikibase'][] = array(
	'path' => __FILE__,
	'name' => 'Purtle',
	'version' => WIKIBASE_VIEW_VERSION,
	'author' => array(
		'Daniel Kinzler',
		'Stas Malyshev',
	),
	'url' => 'https://git.wikimedia.org/blob/mediawiki%2Fextensions%2FWikibase/master/purtle%2FREADME.md',
	'description' => 'A fast, lightweight RDF generator',
	'license-name' => 'GPL-2.0+'
);


$GLOBALS['wgHooks']['UnitTestsList'][] = function( array &$paths ) {
	$paths[] = __DIR__ . '/tests/phpunit';
};
