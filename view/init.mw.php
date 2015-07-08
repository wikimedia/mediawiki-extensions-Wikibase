<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

$GLOBALS['wgExtensionCredits']['wikibase'][] = array(
	'path' => __FILE__,
	'name' => 'Wikibase View',
	'version' => WIKIBASE_VIEW_VERSION,
	'author' => array(
		'[http://www.snater.com H. Snater]',
	),
	'url' => 'https://git.wikimedia.org/summary/mediawiki%2Fextensions%2FWikibaseView',
	'description' => 'Wikibase View',
	'license-name' => 'GPL-2.0+'
);

include 'resources.php';
include 'resources.test.php';

$GLOBALS['wgHooks']['UnitTestsList'][] = function( array &$paths ) {
	$paths[] = __DIR__ . '/tests/phpunit';
};

/**
 * Register ResourceLoader modules with dynamic dependencies.
 *
 * @param ResourceLoader $resourceLoader
 */
$GLOBALS['wgHooks']['ResourceLoaderRegisterModules'][] = function( ResourceLoader $resourceLoader ) {
	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );
	$hasULS = ExtensionRegistry::getInstance()->isLoaded( 'UniversalLanguageSelector' );

	$moduleTemplate = array(
		'remoteExtPath' => '..' . $remoteExtPath[0],
		'position' => 'top' // reducing the time between DOM construction and JS initialisation
	);

	$dependencies = array();
	if ( $hasULS ) {
		$dependencies[] = 'ext.uls.mediawiki';
	}

	$resourceLoader->register(
		'jquery.util.getDirectionality',
		$moduleTemplate + array(
			'localBasePath' => __DIR__ . '/resources/jquery',
			'scripts' => array(
				'jquery.util.getDirectionality.js',
			),
			'dependencies' => $dependencies
		)
	);

	$dependencies = array( 'wikibase' );
	if ( $hasULS ) {
		$dependencies[] = 'ext.uls.mediawiki';
	}

	$resourceLoader->register(
		'wikibase.getLanguageNameByCode',
		$moduleTemplate + array(
			'localBasePath' => __DIR__ . '/resources/wikibase',
			'scripts' => array(
				'wikibase.getLanguageNameByCode.js'
			),
			'dependencies' => $dependencies
		)
	);

	return true;
};
