<?php

namespace Wikibase;

use ExtensionRegistry;
use ResourceLoader;

/**
 * File defining the hook handlers for the WikibaseLib extension.
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 * @author H. Snater < mediawiki@snater.com >
 */
final class LibHooks {

	/**
	 * Hook to add PHPUnit test cases.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnitTestsList
	 *
	 * @param string[] &$paths
	 *
	 * @return bool
	 */
	public static function registerPhpUnitTests( array &$paths ) {
		$paths[] = __DIR__ . '/tests/phpunit/';

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderTestModules
	 *
	 * @param array &$testModules
	 * @param ResourceLoader &$resourceLoader
	 *
	 * @return boolean
	 */
	public static function registerQUnitTests( array &$testModules, ResourceLoader &$resourceLoader ) {
		$testModules['qunit'] = array_merge(
			$testModules['qunit'],
			include __DIR__ . '/tests/qunit/resources.php'
		);

		return true;
	}

	/**
	 * Register ResourceLoader modules with dynamic dependencies.
	 *
	 * @param ResourceLoader $resourceLoader
	 *
	 * @return bool
	 */
	public static function onResourceLoaderRegisterModules( ResourceLoader $resourceLoader ) {
		preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
			. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

		$moduleTemplate = [
			'localBasePath' => __DIR__,
			'remoteExtPath' => '..' . $remoteExtPath[0],
			'position' => 'top' // reducing the time between DOM construction and JS initialisation
		];

		$modules = [
			'wikibase.Site' => $moduleTemplate + [
				'scripts' => [
					'resources/wikibase.Site.js',
				],
				'dependencies' => [
					'mediawiki.util',
					'util.inherit',
					'wikibase',
				],
			],
		];

		$isUlsLoaded = ExtensionRegistry::getInstance()->isLoaded( 'UniversalLanguageSelector' );
		if ( $isUlsLoaded ) {
			$modules['wikibase.Site']['dependencies'][] = 'ext.uls.mediawiki';
		}

		$resourceLoader->register( $modules );

		return true;
	}

}
