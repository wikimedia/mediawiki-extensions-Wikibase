<?php

namespace Wikibase;

use ExtensionRegistry;
use ResourceLoader;

/**
 * File defining the hook handlers for the WikibaseLib extension.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 * @author H. Snater < mediawiki@snater.com >
 */
final class LibHooks {

	/**
	 * Hook to add PHPUnit test cases.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnitTestsList
	 *
	 * @since 0.2
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
	 * @since 0.2 (in repo as RepoHooks::onResourceLoaderTestModules in 0.1)
	 *
	 * @param array &$testModules
	 * @param \ResourceLoader &$resourceLoader
	 *
	 * @return boolean
	 */
	public static function registerQUnitTests( array &$testModules, \ResourceLoader &$resourceLoader ) {
		$testModules['qunit'] = array_merge(
			$testModules['qunit'],
			include( __DIR__ . '/tests/qunit/resources.php' )
		);

		return true;
	}

	/**
	 * Register ResourceLoader modules with dynamic dependencies.
	 *
	 * @param ResourceLoader $resourceLoader
	 */
	public static function onResourceLoaderRegisterModules( ResourceLoader $resourceLoader ) {
		preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
			. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );
		$hasULS = ExtensionRegistry::getInstance()->isLoaded( 'UniversalLanguageSelector' );

		$moduleTemplate = array(
			'localBasePath' => __DIR__ . '/resources',
			'remoteExtPath' => '..' . $remoteExtPath[0],
			'position' => 'top' // reducing the time between DOM construction and JS initialisation
		);

		$dependencies = array(
			'mediawiki.util',
			'util.inherit',
			'wikibase',
		);

		if ( $hasULS ) {
			$dependencies[] = 'ext.uls.mediawiki';
		}

		$resourceLoader->register(
			'wikibase.Site',
			$moduleTemplate + array(
				'scripts' => array(
					'wikibase.Site.js',
				),
				'dependencies' => $dependencies,
			)
		);

		return true;
	}
}
