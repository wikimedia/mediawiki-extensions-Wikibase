<?php

/**
 * File defining the hook handlers for the Wikibase Client extension.
 *
 * @since 0.1
 *
 * @file Wikibase.hooks.php
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @authorNikola Smolenski
 */
final class WikibaseHooks {

	/**
	 * Schema update to set up the needed database tables.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LoadExtensionSchemaUpdates
	 *
	 * @since 0.1
	 *
	 * @param DatabaseUpdater $updater
	 *
	 * @return boolean
	 */
	public static function onSchemaUpdate( DatabaseUpdater $updater ) {
		$updater->addExtensionTable(
			'wb_items_per_site',
			dirname( __FILE__ ) . '/sql/Wikibase.sql'
		);

		$updater->addExtensionTable(
			'wb_aliases',
			dirname( __FILE__ ) . '/sql/AddAliasesTable.sql'
		);

		return true;
	}

	/**
	 * Hook to add PHPUnit test cases.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnitTestsList
	 *
	 * @since 0.1
	 *
	 * @param array $files
	 *
	 * @return boolean
	 */
	public static function registerUnitTests( array &$files ) {
		$testDir = dirname( __FILE__ ) . '/tests/phpunit/includes/';

		$files[] = $testDir . 'WikibaseEntityTests.php';
		$files[] = $testDir . 'WikibaseItemViewTests.php';
		$files[] = $testDir . 'WikibaseSiteTests.php';
		$files[] = $testDir . 'WikibaseSitesTests.php';
		$files[] = $testDir . 'WikibaseUtilsTests.php';

		// api
		$files[] = $testDir . 'api/ApiWikibaseTests.php';
		$files[] = $testDir . 'api/ApiWikibaseModifyItemTest.php';
		$files[] = $testDir . 'api/ApiWikibaseSetAliasesTest.php';

		// wikibaseitem
		$files[] = $testDir . 'WikibaseItem/WikibaseItemTests.php';
		$files[] = $testDir . 'WikibaseItem/WikibaseItemMoveTests.php';
		$files[] = $testDir . 'WikibaseItem/WikibaseItemNewEmptyTests.php';
		$files[] = $testDir . 'WikibaseItem/WikibaseItemNewFromArrayTests.php';
		$files[] = $testDir . 'WikibaseItem/WikibaseItemContentHandlerTests.php';

		return true;
	}

	/**
	 * In Wikidata namespace, page content language is the same as the current user language.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageContentLanguage
	 *
	 * @since 0.1
	 *
	 * @param Title $title
	 * @param Language $pageLanguage
	 * @param Language|StubUserLang $language
	 *
	 * @return boolean
	 */
	public static function onPageContentLanguage( Title $title, Language &$pageLanguage, $language ) {
		global $wgNamespaceContentModels;

		if( array_key_exists( $title->getNamespace(), $wgNamespaceContentModels )
			&& $wgNamespaceContentModels[$title->getNamespace()] === CONTENT_MODEL_WIKIBASE_ITEM ) {
			$pageLanguage = $language;
		}

		return true;
	}

	/**
	 * Add new javascript testing modules. This is called after the addition of MediaWiki core test suites.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderTestModules
	 *
	 * @since 0.1
	 *
	 * @param array $testModules
	 * @param ResourceLoader $resourceLoader
	 *
	 * @return boolean
	 */
	public static function onResourceLoaderTestModules( array &$testModules, ResourceLoader &$resourceLoader ) {
		$testModules['qunit']['wikibase.tests'] = array(
			'scripts' => array(
				'tests/qunit/wikibase.tests.js',
				'tests/qunit/wikibase.Site.tests.js',
				'tests/qunit/wikibase.ui.DescriptionEditTool.tests.js',
				'tests/qunit/wikibase.ui.LabelEditTool.tests.js',
				'tests/qunit/wikibase.ui.SiteLinksEditTool.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableDescription.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableLabel.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableSiteLink.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableValue.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableValue.Interface.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableValue.SiteIdInterface.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableValue.SitePageInterface.tests.js',
				'tests/qunit/wikibase.ui.Toolbar.tests.js',
				'tests/qunit/wikibase.ui.Toolbar.EditGroup.tests.js',
				'tests/qunit/wikibase.ui.Toolbar.Group.tests.js',
				'tests/qunit/wikibase.ui.Toolbar.Label.tests.js',
				'tests/qunit/wikibase.ui.Toolbar.Button.tests.js',
				'tests/qunit/wikibase.ui.Tooltip.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.jQuery.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.jQuery.ui.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.jQuery.ui.inputAutoExpand.tests.js',
			),
			'dependencies' => array(
				'wikibase.tests.qunit.testrunner',
				'wikibase',
				'wikibase.utilities.jQuery',
				'wikibase.ui.Toolbar',
				'wikibase.ui.PropertyEditTool'
			),
			'localBasePath' => dirname( __FILE__ ),
			'remoteExtPath' => 'Wikibase',
		);

		return true;
	}

	/**
	 * Allows overriding if the pages in a certain namespace can be moved or not.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/NamespaceIsMovable
	 *
	 * @since 0.1
	 *
	 * @param integer $index
	 * @param boolean $movable
	 *
	 * @return boolean
	 */
	public static function onNamespaceIsMovable( $index, &$movable ) {
		if ( in_array( $index, array( WB_NS_DATA, WB_NS_DATA_TALK ) ) ) {
			$movable = false;
		}

		return true;
	}

	/**
	 * Called when a revision was inserted due to an edit.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/NewRevisionFromEditComplete
	 *
	 * @since 0.1
	 *
	 * @param weirdStuffButProbablyWikiPage $article
	 * @param Revision $revision
	 * @param integer $baseID
	 * @param User $user
	 *
	 * @return boolean
	 */
	public static function onNewRevisionFromEditComplete( $article, Revision $revision, $baseID, User $user ) {
		$newItem = $article->getContent();

		if ( $newItem->getModel() === CONTENT_MODEL_WIKIBASE_ITEM ) {
			$oldItem = is_null( $revision->getParentId() ) ? WikibaseItem::newEmpty() : Revision::newFromId( $revision->getParentId() )->getContent();

			$diff = new WikibaseItemDiff( $oldItem, $newItem );

			if ( $diff->hasChanges() ) {
				$dbw = wfGetDB( DB_MASTER );

				$dbw->begin();

				foreach ( $diff->getChanges() as /* Wikibase\Change */ $change ) {
					$change->save();
				}

				$dbw->commit();
			}
		}

		return true;
	}

}
