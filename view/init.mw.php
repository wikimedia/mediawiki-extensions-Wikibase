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

include __DIR__ . '/resources.php';
include __DIR__ . '/resources.test.php';

$GLOBALS['wgMessagesDirs']['WikibaseView'] = [];
include __DIR__ . '/lib/i18n.php';

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
