<?php

namespace Wikibase;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * File defining the hook handlers for the WikibaseLib extension.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
final class LibHooks {

	/**
	 * Hook to add PHPUnit test cases.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnitTestsList
	 *
	 * @since 0.2
	 *
	 * @param string[] $files
	 *
	 * @return boolean
	 */
	public static function registerPhpUnitTests( array &$files ) {
		// @codeCoverageIgnoreStart
		$directoryIterator = new RecursiveDirectoryIterator( __DIR__ . '/tests/phpunit/' );

		/**
		 * @var SplFileInfo $fileInfo
		 */
		foreach ( new RecursiveIteratorIterator( $directoryIterator ) as $fileInfo ) {
			if ( substr( $fileInfo->getFilename(), -8 ) === 'Test.php' ) {
				$files[] = $fileInfo->getPathname();
			}
		}

		return true;
		// @codeCoverageIgnoreEnd
	}

	/**
	 * Add new javascript testing modules. This is called after the addition of MediaWiki core test suites.
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
		$testModules['qunit']['wikibase.lib.tests'] = array(
			'scripts' => array(
				'tests/qunit/templates.tests.js',
				'tests/qunit/wikibase.tests.js',

				'tests/qunit/wikibase.Site.tests.js',

				'tests/qunit/wikibase.RepoApi/wikibase.RepoApi.tests.js',
				'tests/qunit/wikibase.RepoApi/wikibase.RepoApiError.tests.js',

				'tests/qunit/wikibase.ui.Toolbar.tests.js',
				'tests/qunit/wikibase.ui.Toolbar.Group.tests.js',
				'tests/qunit/wikibase.ui.Toolbar.Label.tests.js',
				'tests/qunit/wikibase.ui.Toolbar.Button.tests.js',
				'tests/qunit/wikibase.ui.Tooltip.tests.js',

				'tests/qunit/wikibase.utilities/wikibase.utilities.ObservableObject.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.ui.StatableObject.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.jQuery.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.jQuery.NativeEventHandler.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.jQuery.NativeEventHandler.testsOnObject.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.jQuery.NativeEventHandler.testsOnWidget.js',

				'tests/qunit/jquery.wikibase/jquery.wikibase.siteselector.tests.js',

			),
			'dependencies' => array(
				'wikibase.tests.qunit.testrunner',
				'wikibase.utilities',
				'wikibase.utilities.jQuery',
				'wikibase.ui.Toolbar',
				'wikibase.ui.Tooltip',
				'wikibase.RepoApi',
				'wikibase.RepoApiError',
				'jquery.ui.suggester',
				'jquery.client',
				'jquery.eachchange',
				'jquery.wikibase.siteselector',
			),
			'localBasePath' => __DIR__,
			'remoteExtPath' => 'Wikibase/lib',
		);

		return true;
	}
}
