<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

$GLOBALS['wgExtensionCredits']['wikibase'][] = [
	'path' => __DIR__,
	'name' => 'Wikibase View',
	'author' => [
		'The Wikidata team',
	],
	'url' => 'https://phabricator.wikimedia.org/diffusion/EWBA/browse/master/view/',
	'description' => 'View component for the Wikibase Repository',
	'license-name' => 'GPL-2.0-or-later'
];

$GLOBALS['wgResourceModules'] = array_merge(
	$GLOBALS['wgResourceModules'],
	include __DIR__ . '/lib/resources.php',
	include __DIR__ . '/resources/resources.php'
);

$GLOBALS['wgHooks']['ResourceLoaderTestModules'][] = function(
	array &$testModules,
	ResourceLoader $resourceLoader
) {
	$testModules['qunit'] = array_merge(
		$testModules['qunit'],
		include __DIR__ . '/lib/resources.test.php',
		include __DIR__ . '/tests/qunit/resources.php'
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
	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$moduleTemplate = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0],
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
