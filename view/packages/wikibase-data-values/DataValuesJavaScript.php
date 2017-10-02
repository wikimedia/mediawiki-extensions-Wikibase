<?php

// @codeCoverageIgnoreStart

if ( defined( 'DATA_VALUES_JAVASCRIPT_VERSION' ) ) {
	// Do not initialize more than once.
	return 1;
}

define( 'DATA_VALUES_JAVASCRIPT_VERSION', '0.9.0' );

// Include the composer autoloader if it is present.
if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

if ( !defined( 'MEDIAWIKI' ) ) {
	return 1;
}

$GLOBALS['wgExtensionCredits']['datavalues'][] = [
	'path' => __DIR__,
	'name' => 'DataValues JavaScript',
	'version' => DATA_VALUES_JAVASCRIPT_VERSION,
	'author' => [
		'[https://www.mediawiki.org/wiki/User:Danwe Daniel Werner]',
		'[http://www.snater.com H. Snater]',
		'[https://www.mediawiki.org/wiki/User:Jeroen_De_Dauw Jeroen De Dauw]',
	],
	'url' => 'https://github.com/wmde/DataValuesJavascript',
	'description' => 'JavaScript related to the DataValues library',
	'license-name' => 'GPL-2.0+'
];
