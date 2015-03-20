<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

$GLOBALS['wgExtensionCredits']['wikibase'][] = array(
	'path' => __FILE__,
	'name' => 'Wikibase Changes',
	'version' => WIKIBASE_CHANGES_VERSION,
	'author' => array(
		'The Wikidata team',
	),
	'url' => 'https://mediawiki.org/wiki/Extension:Wikibase#Changes',
	'description' => 'Wikibase Changes',
	'license-name' => 'GPL-2.0+'
);

$GLOBALS['wgHooks']['UnitTestList'][] = function ( array &$paths ) {
	$paths[] = __DIR__ . '/tests/phpunit/';
};