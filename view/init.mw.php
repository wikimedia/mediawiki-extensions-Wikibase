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
 *
 * @return bool
 */
$GLOBALS['wgHooks']['ResourceLoaderRegisterModules'][] = function( ResourceLoader $resourceLoader ) {
	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0],
		'position' => 'top' // reducing the time between DOM construction and JS initialisation
	);

	$modules = array(
		'jquery.util.getDirectionality' => $moduleTemplate + array(
			'scripts' => array(
				'resources/jquery/jquery.util.getDirectionality.js',
			),
		),
		'wikibase.getLanguageNameByCode' => $moduleTemplate + array(
			'scripts' => array(
				'resources/wikibase/wikibase.getLanguageNameByCode.js',
			),
			'dependencies' => array(
				'wikibase',
			),
		),
	);

	$isUlsLoaded = ExtensionRegistry::getInstance()->isLoaded( 'UniversalLanguageSelector' );
	if ( $isUlsLoaded ) {
		$modules['jquery.util.getDirectionality']['dependencies'][] = 'ext.uls.mediawiki';
		$modules['wikibase.getLanguageNameByCode']['dependencies'][] = 'ext.uls.mediawiki';
	}

	$resourceLoader->register( $modules );

	return true;
};
