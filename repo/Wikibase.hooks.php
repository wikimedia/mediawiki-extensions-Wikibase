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
 * @author Nikola Smolenski
 * @author Daniel Werner
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
			'wb_items',
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
	 * @param array &$files
	 *
	 * @return boolean
	 */
	public static function registerUnitTests( array &$files ) {
		$testFiles = array(
			'ItemMove',
			'ChangeNotifier',
			'EntityHandler',
			'ItemContent',
			'ItemDeletionUpdate',
			'ItemHandler',
			'ItemView',

			'api/ApiJSONP',
			'api/ApiJSONPComplete',
			'api/ApiLanguageAttribute',
			'api/ApiSetAliases',
			'api/ApiSetItem',
			'api/ApiLinkSite',

			'specials/SpecialCreateItem',
			'specials/SpecialItemByLabel',
			'specials/SpecialItemByTitle',
		);

		foreach ( $testFiles as $file ) {
			$files[] = dirname( __FILE__ ) . '/tests/phpunit/includes/' . $file . 'Test.php';
		}

		return true;
	}

	/**
	 * In Wikidata namespace, page content language is the same as the current user language.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageContentLanguage
	 *
	 * @since 0.1
	 *
	 * @param Title $title
	 * @param Language &$pageLanguage
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
	 * @param array &$testModules
	 * @param ResourceLoader &$resourceLoader
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
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableValue.ListInterface.tests.js',
				'tests/qunit/wikibase.ui.Toolbar.tests.js',
				'tests/qunit/wikibase.ui.Toolbar.EditGroup.tests.js',
				'tests/qunit/wikibase.ui.Toolbar.Group.tests.js',
				'tests/qunit/wikibase.ui.Toolbar.Label.tests.js',
				'tests/qunit/wikibase.ui.Toolbar.Button.tests.js',
				'tests/qunit/wikibase.ui.Tooltip.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.jQuery.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.jQuery.PersistentPromisor.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.jQuery.ui.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.jQuery.ui.inputAutoExpand.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.jQuery.ui.tagadata.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.jQuery.ui.eachchange.tests.js',
			),
			'dependencies' => array(
				'wikibase.tests.qunit.testrunner',
				'wikibase',
				'wikibase.utilities.jQuery',
				'wikibase.ui.Toolbar',
				'wikibase.ui.PropertyEditTool'
			),
			'localBasePath' => dirname( __FILE__ ),
			'remoteExtPath' => 'Wikibase/repo',
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
		if ( $article->getContent()->getModel() === CONTENT_MODEL_WIKIBASE_ITEM ) {
			/**
			 * @var $newItem \Wikibase\Item
			 */
			$newItem = $article->getContent()->getItem();

			$oldItem = is_null( $revision->getParentId() ) ?
				Wikibase\ItemObject::newEmpty()
				: Revision::newFromId( $revision->getParentId() )->getContent()->getItem();

			$change = \Wikibase\ItemChange::newFromItems( $oldItem, $newItem );

			$change->setFields( array(
				'revision_id' => $revision->getId(),
				'user_id' => $user->getId(),
				'object_id' => $newItem->getId(),
				'time' => $revision->getTimestamp(),
			) );

			\Wikibase\ChangeNotifier::singleton()->handleChange( $change );
		}

		return true;
	}

	/**
	 * Adds default settings.
	 * Setting name (string) => setting value (mixed)
	 *
	 * @param array &$settings
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public static function onWikibaseDefaultSettings( array &$settings ) {
		$settings = array_merge(
			$settings,
			array(
				// alternative: application/vnd.php.serialized
				'serializationFormat' => CONTENT_FORMAT_JSON,

				// Defaults to turn off use of keys
				// set to true will always return the key form
				'apiUseKeys' => false,

				// Set API in debug mode
				// do not turn on in production!
				'apiInDebug' => false,

				// Additional settings for API when debugging is on to
				// facilitate testing, do not turn on in production!
				'apiDebugWithWrite' => true,
				'apiDebugWithPost' => false,
				'apiDebugWithRights' => false,
				'apiDebugWithTokens' => false,

				// Which formats to use with keys when there are a "usekeys" in the URL
				// undefined entries are interpreted as false
				'formatsWithKeys' => array(
					'json' => true,
					'jsonfm' => true,
					'php' => false,
					'phpfm' => false,
					'wddx' => false,
					'wddxfm' => false,
					'xml' => false,
					'xmlfm' => false,
					'yaml' => true,
					'yamlfm' => true,
					'raw' => true,
					'rawfm' => true,
					'txtfm' => true,
					'dbg' => true,
					'dbgfm' => true,
					'dump' => true,
					'dumpfm' => true,
				),
				// settings for the user agent
				'clientTimeout' => 120, // this is before final timeout, the maxlag value and then some
				'clientPageOpts' => array(
					'userAgent' => 'Wikibase',
				),
				'clientPageArgs' => array(
					'action' => 'query',
					'prop' => 'info',
					'redirects' => true,
					'converttitles' => true,
					'format' => 'json',
					//TODO: This should REALLY be handled somehow as without it we could run into lots of trouble
					//'maxage' => 5, // filter down repeated clicks, don't let clicky folks loose to fast
					//'smaxage' => 15, // give the proxy some time, don't let clicky folks loose to fast
					//'maxlag' => 100, // time to wait on a lagging server, hanging on for 100 sec is very aggressive
				),
			)
		);

		return true;
	}

	/**
	 * Alter the structured navigation links in SkinTemplates.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinTemplateNavigation
	 *
	 * @since 0.1
	 *
	 * @param SkinTemplate $sktemplate
	 * @param array $links
	 *
	 * @return boolean
	 */
	public static function onPageTabs( SkinTemplate &$sktemplate, array &$links ) {
		if ( in_array( $sktemplate->getTitle()->getContentModel(), array( CONTENT_MODEL_WIKIBASE_ITEM ) ) ) {
			unset( $links['views']['edit'] );
		}

		return true;
	}

}
