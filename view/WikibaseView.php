<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

define( 'WIKIBASE_VIEW_VERSION', '0.1-dev' );

// load parts already converted to extension registration
wfLoadExtension( 'WikibaseView', __DIR__ . '/../extension-view-wip.json' );

// Load autoload info as long as extension classes are not PSR-4-autoloaded
require_once __DIR__  . '/autoload.php';

$GLOBALS['wgResourceModules'] = array_merge(
	$GLOBALS['wgResourceModules'],
	require __DIR__ . '/lib/resources.php',
	require __DIR__ . '/resources/resources.php'
);

$GLOBALS['wgHooks']['ResourceLoaderTestModules'][] = function(
	array &$testModules,
	ResourceLoader $resourceLoader
) {
	$testModules['qunit'] = array_merge(
		$testModules['qunit'],
		require __DIR__ . '/lib/resources.test.php',
		require __DIR__ . '/tests/qunit/resources.php'
	);
};

$GLOBALS['wgMessagesDirs']['WikibaseView'] = [
	__DIR__ . '/lib/wikibase-data-values-value-view/i18n',
];

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
	$moduleTemplate = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'Wikibase/view',
	];

	$modules = [
		'jquery.util.getDirectionality' => $moduleTemplate + [
			'scripts' => [
				'resources/jquery/jquery.util.getDirectionality.js',
			],
		],
		'wikibase.getLanguageNameByCode' => $moduleTemplate + [
			'scripts' => [
				'resources/wikibase/wikibase.getLanguageNameByCode.js',
			],
			'dependencies' => [
				'wikibase',
			],
			'targets' => [ 'desktop', 'mobile' ]
		],
	];

	$isUlsLoaded = ExtensionRegistry::getInstance()->isLoaded( 'UniversalLanguageSelector' );
	if ( $isUlsLoaded ) {
		$modules['jquery.util.getDirectionality']['dependencies'][] = 'ext.uls.mediawiki';
		$modules['wikibase.getLanguageNameByCode']['dependencies'][] = 'ext.uls.mediawiki';
	}

	$resourceLoader->register( $modules );

	return true;
};
