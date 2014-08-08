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
	 * TODO: Move into a file with only this definition.
	 *
	 * @since 0.2 (in repo as RepoHooks::onResourceLoaderTestModules in 0.1)
	 *
	 * @param array &$testModules
	 * @param \ResourceLoader &$resourceLoader
	 *
	 * @return boolean
	 */
	public static function registerQUnitTests( array &$testModules, \ResourceLoader &$resourceLoader ) {
		$remoteExtPathParts = explode( DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR , __DIR__, 2 );
		$moduleBase = array(
			'localBasePath' => __DIR__,
			'remoteExtPath' => $remoteExtPathParts[1],
		);

		$testModules['qunit']['wikibase.tests.qunit.testrunner'] = $moduleBase + array(
			'scripts' => 'tests/qunit/data/testrunner.js',
			'dependencies' => array(
				'test.mediawiki.qunit.testrunner',
				'wikibase',
			),
			'position' => 'top'
		);

		// TODO: Split into test modules per QUnit module.
		$testModules['qunit']['wikibase.tests'] = $moduleBase + array(
			'scripts' => array(
				'tests/qunit/templates.tests.js',
				'tests/qunit/wikibase.tests.js',

				'tests/qunit/wikibase.RepoApi/wikibase.RepoApi.tests.js',
				'tests/qunit/wikibase.RepoApi/wikibase.RepoApiError.tests.js',

				'tests/qunit/wikibase.ui.AliasesEditTool.tests.js',
				'tests/qunit/wikibase.ui.DescriptionEditTool.tests.js',
				'tests/qunit/wikibase.ui.LabelEditTool.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableAliases.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableDescription.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableLabel.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableValue.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableValue.Interface.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableValue.ListInterface.tests.js',

				'tests/qunit/wikibase.utilities/wikibase.utilities.ClaimGuidGenerator.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.GuidGenerator.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.newExtension.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.ObservableObject.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.ui.StatableObject.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.jQuery.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.jQuery.PersistentPromisor.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.jQuery.ui.tagadata.tests.js',

				'tests/qunit/jquery.wikibase/jquery.wikibase.entityselector.tests.js',
				'tests/qunit/jquery.wikibase/jquery.wikibase.siteselector.tests.js',
			),
			'dependencies' => array(
				'wikibase.tests.qunit.testrunner',
				'wikibase',
				'wikibase.RepoApi',
				'wikibase.AbstractedRepoApi',
				'wikibase.datamodel', // For RepoApi.tests
				'wikibase.store.FetchedContent',
				'wikibase.templates',
				'wikibase.utilities',
				'wikibase.utilities.ClaimGuidGenerator',
				'wikibase.utilities.GuidGenerator',
				'wikibase.utilities.jQuery',
				'wikibase.ui.PropertyEditTool',
				'jquery.ui.suggester',
				'jquery.wikibase.entityselector',
				'jquery.NativeEventHandler',
				'jquery.client',
				'jquery.event.special.eachchange',
				'util.inherit',
			)
		);

		$testModules['qunit']['jquery.wikibase.claimgrouplabelscroll.tests'] = $moduleBase + array(
			'scripts' => array(
				'tests/qunit/jquery.wikibase/jquery.wikibase.claimgrouplabelscroll.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.claimgrouplabelscroll'
			),
		);

		$testModules['qunit']['jquery.wikibase.claimview.tests'] = $moduleBase + array(
			'scripts' => array(
				'tests/qunit/jquery.wikibase/jquery.wikibase.claimview.tests.js',
			),
			'dependencies' => array(
				'dataValues.values',
				'jquery.valueview',
				'jquery.wikibase.claimview',
				'mediawiki.Title',
				'valueFormatters',
				'wikibase.AbstractedRepoApi',
				'wikibase.datamodel',
				'wikibase.RepoApi',
				'wikibase.store.EntityStore',
				'wikibase.store.FetchedContent',
				'wikibase.ValueViewBuilder',
			),
		);

		$testModules['qunit']['jquery.wikibase.listview.tests'] = $moduleBase + array(
			'scripts' => array(
				'tests/qunit/jquery.wikibase/jquery.wikibase.listview.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.listview',
			),
		);

		$testModules['qunit']['jquery.wikibase.pagesuggester.tests'] = $moduleBase + array(
			'scripts' => array(
				'tests/qunit/jquery.wikibase/jquery.wikibase.pagesuggester.tests.js'
			),
			'dependencies' => array(
				'jquery.wikibase.pagesuggester',
			),
		);

		$testModules['qunit']['jquery.wikibase.referenceview.tests'] = $moduleBase + array(
			'scripts' => array(
				'tests/qunit/jquery.wikibase/jquery.wikibase.referenceview.tests.js',
			),
			'dependencies' => array(
				'jquery.valueview.ExpertStore',
				'jquery.wikibase.referenceview',
				'mediawiki.Title',
				'wikibase.AbstractedRepoApi',
				'wikibase.datamodel',
				'wikibase.RepoApi',
				'wikibase.store.FetchedContent',
				'wikibase.store.EntityStore',
				'wikibase.ValueViewBuilder',
				'valueFormatters'
			),
		);

		$testModules['qunit']['jquery.wikibase.sitelinkview.tests'] = $moduleBase + array(
			'scripts' => array(
				'tests/qunit/jquery.wikibase/jquery.wikibase.sitelinkview.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.sitelinkview',
				'wikibase.datamodel',
				'wikibase.store.EntityStore',
				'wikibase.tests.qunit.testrunner',
			),
		);

		$testModules['qunit']['jquery.wikibase.sitelinklistview.tests'] = $moduleBase + array(
			'scripts' => array(
				'tests/qunit/jquery.wikibase/jquery.wikibase.sitelinklistview.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.sitelinklistview',
				'wikibase.datamodel',
				'wikibase.store.EntityStore',
				'wikibase.tests.qunit.testrunner',
			),
		);

		$testModules['qunit']['jquery.wikibase.statementview.tests'] = $moduleBase + array(
				'scripts' => array(
					'tests/qunit/jquery.wikibase/jquery.wikibase.statementview.RankSelector.tests.js',
				),
				'dependencies' => array(
					'jquery.wikibase.statementview',
					'wikibase.datamodel',
				),
			);

		$testModules['qunit']['jquery.wikibase.snaklistview.tests'] = $moduleBase + array(
			'scripts' => array(
				'tests/qunit/jquery.wikibase/jquery.wikibase.snaklistview.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.snaklistview',
				'wikibase.store.EntityStore',
				'wikibase.store.FetchedContent',
				'wikibase.ValueViewBuilder',
				'wikibase.datamodel',
				'mediawiki.Title',
				'jquery.valueview',
				'valueFormatters'
			),
		);

		$testModules['qunit']['jquery.wikibase.wbtooltip.tests'] = $moduleBase + array(
			'scripts' => array(
				'tests/qunit/jquery.wikibase/jquery.wikibase.wbtooltip.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.wbtooltip',
			),
		);

		$testModules['qunit']['wikibase.compileEntityStoreFromMwConfig.tests'] = $moduleBase + array(
			'scripts' => array(
				'tests/qunit/wikibase.compileEntityStoreFromMwConfig.tests.js',
			),
			'dependencies' => array(
				'wikibase.compileEntityStoreFromMwConfig',
				'wikibase.tests.qunit.testrunner'
			),
		);

		$testModules['qunit']['tests/qunit/wikibase.dataTypes/wikibase.dataTypes.tests'] = $moduleBase + array(
			'scripts' => array(
				'tests/qunit/wikibase.dataTypes/wikibase.dataTypes.tests.js',
			),
			'dependencies' => array(
				'dataTypes.DataTypeStore',
				'wikibase.dataTypes',
			),
		);

		$testModules['qunit']['wikibase.store.EntityStore.tests'] = $moduleBase + array(
			'scripts' => array(
				'tests/qunit/wikibase.store/store.EntityStore.tests.js',
			),
			'dependencies' => array(
				'wikibase.store.EntityStore',
				'wikibase.tests.qunit.testrunner'
			),
		);

		$testModules['qunit']['wikibase.experts.EntityIdInput.tests'] = $moduleBase + array(
			'scripts' => array(
				'tests/qunit/experts/EntityIdInput.tests.js',
			),
			'dependencies' => array(
				'wikibase.experts.EntityIdInput',
				'wikibase.tests.qunit.testrunner',
			),
		);

		$testModules['qunit']['wikibase.parsers.EntityIdParser.tests'] = $moduleBase + array(
			'scripts' => array(
				'tests/qunit/wikibase.tests.js',
				'tests/qunit/parsers/EntityIdParser.tests.js',
			),
			'dependencies' => array(
				'util.inherit',
				'valueParsers.tests',
				'wikibase.datamodel',
				'wikibase.EntityIdParser',
				'wikibase.tests.qunit.testrunner',
			),
		);

		$testModules['qunit']['wikibase.sites.tests'] = $moduleBase + array(
			'scripts' => array(
				'tests/qunit/wikibase.Site.tests.js',
				'tests/qunit/wikibase.sites.tests.js',
			),
			'dependencies' => array(
				'wikibase',
				'wikibase.sites',
				'wikibase.tests.qunit.testrunner',
			),
		);

		$testModules['qunit']['wikibase.ValueViewBuilder.tests'] = $moduleBase + array(
			'scripts' => array(
				'tests/qunit/wikibase.ValueViewBuilder.tests.js'
			),
			'dependencies' => array(
				'test.sinonjs',
				'wikibase.ValueViewBuilder'
			)
		);

		$testModules['qunit'] = array_merge(
			$testModules['qunit'],
			include( __DIR__ . '/tests/qunit/jquery.wikibase/toolbar/resources.php' )
		);

		return true;
	}
}
