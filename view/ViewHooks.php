<?php

namespace Wikibase;

use ExtensionRegistry;
use ResourceLoader;

/**
 * File defining the hook handlers for the WikibaseView extension.
 *
 * @license GPL-2.0-or-later
 */
final class ViewHooks {

	/**
	 * Register ResourceLoader modules with dynamic dependencies.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderTestModules
	 *
	 * @param ResourceLoader $resourceLoader
	 *
	 * @return bool
	 */
	public static function onResourceLoaderRegisterModules( ResourceLoader $resourceLoader ) {
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
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderTestModules
	 *
	 * @param array &$testModules
	 * @param ResourceLoader $resourceLoader
	 */
	public static function onResourceLoaderTestModules(
		array &$testModules,
		ResourceLoader $resourceLoader
	) {
		$testModules['qunit'] = array_merge(
			$testModules['qunit'],
			require __DIR__ . '/lib/resources.test.php',
			require __DIR__ . '/tests/qunit/resources.php'
		);
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnitTestsList
	 *
	 * @param string[] &$paths
	 */
	public static function onUnitTestsList( array &$paths ) {
		$paths[] = __DIR__ . '/tests/phpunit';
	}

}
