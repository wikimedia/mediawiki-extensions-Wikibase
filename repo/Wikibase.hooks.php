<?php

namespace Wikibase;
use Title, Language, User, Revision, WikiPage, EditPage, ContentHandler, Html;


/**
 * File defining the hook handlers for the Wikibase Client extension.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Nikola Smolenski
 * @author Daniel Werner
 * @author Michał Łazowik
 */
final class RepoHooks {

	/**
	 * Schema update to set up the needed database tables.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LoadExtensionSchemaUpdates
	 *
	 * @since 0.1
	 *
	 * @param \DatabaseUpdater $updater
	 *
	 * @return boolean
	 */
	public static function onSchemaUpdate( \DatabaseUpdater $updater ) {
		if ( Settings::get( 'defaultStore' ) === 'sqlstore' ) {
			/**
			 * @var SQLStore $store
			 */
			$store = StoreFactory::getStore( 'sqlstore' );
			$store->doSchemaUpdate( $updater );
		}

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
		// @codeCoverageIgnoreStart
		$testFiles = array(
			'ItemMove',
			'ItemContentDiffView',
			'ItemMove',
			'ItemView', 
			'Autocomment',
			'EditEntity',

			'actions/EditEntityAction',

			'api/ApiBotEdit',
			'api/ApiEditPage',
			'api/ApiGetItems',
			'api/ApiJSONPComplete',
			'api/ApiJSONP',
			'api/ApiLabel',
			'api/ApiDescription',
			'api/ApiPermissions',
			'api/ApiSetAliases',
			'api/ApiSetItem',
			'api/ApiSetSiteLink',
			'api/ApiLinkTitles',

			'content/EntityHandler',
			'content/ItemContent',
			'content/ItemHandler',
			'content/PropertyContent',
			'content/PropertyHandler',
			'content/QueryContent',
			'content/QueryHandler',

			'specials/SpecialCreateItem',
			'specials/SpecialItemDisambiguation',
			'specials/SpecialItemByTitle',

			'store/EntityDeletionHandler',
			'store/EntityUpdateHandler',
			'store/IdGenerator',
			'store/StoreFactory',
			'store/Store',
			'store/TermLookup',

			'updates/ItemDeletionUpdate',
			'updates/ItemStructuredSave',
		);

		foreach ( $testFiles as $file ) {
			$files[] = __DIR__ . '/tests/phpunit/includes/' . $file . 'Test.php';
		}

		return true;
		// @codeCoverageIgnoreEnd
	}

	/**
	 * In Wikidata namespace, page content language is the same as the current user language.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageContentLanguage
	 *
	 * @since 0.1
	 *
	 * @param Title $title
	 * @param Language &$pageLanguage
	 * @param Language|\StubUserLang $language
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
	 * @param \ResourceLoader &$resourceLoader
	 *
	 * @return boolean
	 */
	public static function onResourceLoaderTestModules( array &$testModules, \ResourceLoader &$resourceLoader ) {
		$testModules['qunit']['wikibase.tests'] = array(
			'scripts' => array(
				'tests/qunit/wikibase.tests.js',
				'tests/qunit/wikibase.Site.tests.js',
				'tests/qunit/wikibase.ui.AliasesEditTool.tests.js',
				'tests/qunit/wikibase.ui.DescriptionEditTool.tests.js',
				'tests/qunit/wikibase.ui.LabelEditTool.tests.js',
				'tests/qunit/wikibase.ui.SiteLinksEditTool.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableAliases.tests.js',
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
				'tests/qunit/wikibase.utilities/wikibase.utilities.inherit.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.newExtension.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.ObservableObject.tests.js',
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
				'wikibase.utilities',
				'wikibase.utilities.jQuery',
				'wikibase.ui.Toolbar',
				'wikibase.ui.PropertyEditTool'
			),
			'localBasePath' => __DIR__,
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
			 * @var $newItem Item
			 */
			$newItem = $article->getContent()->getItem();

			if ( is_null( $revision->getParentId() ) ) {
				$change = EntityCreation::newFromEntity( $newItem );
			}
			else {
				$change = EntityUpdate::newFromEntities(
					Revision::newFromId( $revision->getParentId() )->getContent()->getItem(),
					$newItem
				);
			}

			$change->setFields( array(
				'revision_id' => $revision->getId(),
				'user_id' => $user->getId(),
				'object_id' => $newItem->getId(),
				'time' => $revision->getTimestamp(),
			) );

			ChangeNotifier::singleton()->handleChange( $change );
		}

		return true;
	}

	/**
	 * Occurs after the delete article request has been processed.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleDeleteComplete
	 *
	 * @since 0.1
	 *
	 * @param WikiPage $wikiPage
	 * @param User $user
	 * @param string $reason
	 * @param integer $id
	 *
	 * @return boolean
	 */
	public static function onArticleDeleteComplete( WikiPage &$wikiPage, User &$user, $reason, $id ) {
		// This is a temporary hack since the archive table does not correctly have the data we need.
		// Once this is fixed this can go, and we can use the commented out code later in this method.
		if ( $wikiPage->getTitle()->getNamespace() !== WB_NS_DATA ) {
			return true;
		}

		$dbw = wfGetDB( DB_MASTER );

		$archiveEntry = $dbw->selectRow(
			'archive',
			array(
				'ar_user',
				'ar_text_id',
				'ar_rev_id',
				'ar_timestamp',
				'ar_content_format',
			),
			array(
				'ar_page_id' => $id,
				// 'ar_content_model' => CONTENT_MODEL_WIKIBASE_ITEM,
			),
			__METHOD__
		);

		if ( $archiveEntry !== false ) {
			$textEntry = $dbw->selectRow(
				'text',
				'old_text',
				array( 'old_id' => $archiveEntry->ar_text_id ),
				__METHOD__
			);

			if ( $textEntry !== false ) {
				$itemHandler = ContentHandler::getForModelID( CONTENT_MODEL_WIKIBASE_ITEM );
				$itemContent = $itemHandler->unserializeContent( $textEntry->old_text/* , $archiveEntry->ar_content_format */ );
				$item = $itemContent->getItem();
				$change = EntityDeletion::newFromEntity( $item );

				$change->setFields( array(
					'revision_id' => $archiveEntry->ar_rev_id,
					'user_id' => $archiveEntry->ar_user,
					'object_id' => $item->getId(),
					'time' => $archiveEntry->ar_timestamp,
				) );

				ChangeNotifier::singleton()->handleChange( $change );
			}
		}

		return true;
	}

	/**
	 * Allows to add user preferences.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/GetPreferences
	 *
	 * NOTE: Might make sense to put the inner functionality into a well structured Preferences file once this
	 *       becomes more.
	 *
	 * @since 0.1
	 *
	 * @param User $user
	 * @param array &$preferences
	 *
	 * @return bool
	 */
	public static function onGetPreferences( User $user, array &$preferences ) {
		$preferences['wb-languages'] = array(
			'type' => 'multiselect',
			'usecheckboxes' => false,
			'label-message' => 'wikibase-setting-languages',
			'options' => $preferences['language']['options'], // all languages available in 'language' selector
			'section' => 'personal/i18n',
			'prefix' => 'wb-languages-',
		);

		return true;
	}

	/**
	 * Called after fetching the core default user options.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UserGetDefaultOptions
	 *
	 * @param array &$defaultOptions
	 *
	 * @return bool
	 */
	public static function onUserGetDefaultOptions( array &$defaultOptions ) {
		// pre-select default language in the list of fallback languages
		$defaultLang = $defaultOptions['language'];
		$defaultOptions[ 'wb-languages-' . $defaultLang ] = 1;

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

				// Defaults to turn on deletion of empty items
				// set to true will always delete empty items
				'apiDeleteEmpty' => false,

				// Set API in debug mode
				// do not turn on in production!
				'apiInDebug' => false,

				// Additional settings for API when debugging is on to
				// facilitate testing, do not turn on in production!
				'apiDebugWithWrite' => true,
				'apiDebugWithPost' => false,
				'apiDebugWithRights' => false,
				'apiDebugWithTokens' => false,

				// settings for the user agent
				//TODO: This should REALLY be handled somehow as without it we could run into lots of trouble
				'clientTimeout' => 10, // this is before final timeout, without maxlag or maxage we can't hang around
				//'clientTimeout' => 120, // this is before final timeout, the maxlag value and then some
				'clientPageOpts' => array(
					'userAgent' => 'Wikibase',
				),

				'defaultStore' => 'sqlstore',
			)
		);

		return true;
	}

	/**
	 * Modify line endings on history page.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageHistoryLineEnding
	 *
	 * @since 0.1
	 *
	 * @param \HistoryPager $history
	 * @param object &$row
	 * @param string &$s
	 * @param array &$classes
	 *
	 * @return boolean
	 */
	public static function onPageHistoryLineEnding( \HistoryPager $history, &$row, &$s, array &$classes  ) {
		$article = $history->getArticle();
		$rev = new Revision( $row );

		if ( in_array( $history->getTitle()->getContentModel(), array( CONTENT_MODEL_WIKIBASE_ITEM ) )
			&& $article->getPage()->getLatest() !== $rev->getID()
			&& $rev->getTitle()->quickUserCan( 'edit', $history->getUser() )
		) {
			$link = \Linker::linkKnown(
				$rev->getTitle(),
				$history->msg( 'wikibase-restoreold' )->escaped(),
				array(),
				array(
					'action'	=> 'edit',
					'restore'	=> $rev->getId()
				)
			);

			$s .= " " . $history->msg( 'parentheses' )->rawParams( $link )->escaped();
		}

		return true;
	}

	/**
	 * Alter the structured navigation links in SkinTemplates.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinTemplateNavigation
	 *
	 * @since 0.1
	 *
	 * @param \SkinTemplate $sktemplate
	 * @param array $links
	 *
	 * @return boolean
	 */
	public static function onPageTabs( \SkinTemplate &$sktemplate, array &$links ) {
		$title = $sktemplate->getTitle();
		$request = $sktemplate->getRequest();

		if ( in_array( $title->getContentModel(), array( CONTENT_MODEL_WIKIBASE_ITEM ) ) ) {
			unset( $links['views']['edit'] );

			if ( $title->quickUserCan( 'edit', $sktemplate->getUser() ) ) {
				$old = !$sktemplate->isRevisionCurrent()
					&& !$request->getCheck( 'diff' );

				$restore = $request->getCheck( 'restore' );

				if ( $old || $restore ) {
					// insert restore tab into views array, at the second position

					$revid = $restore ? $request->getText( 'restore' ) : $sktemplate->getRevisionId();

					$head = array_slice( $links['views'], 0, 1);
					$tail = array_slice( $links['views'], 1 );
					$neck['restore'] = array(
						'class' => $restore ? 'selected' : false,
						'text' => $sktemplate->getLanguage()->ucfirst(
								wfMessage( 'wikibase-restoreold' )->text()
							),
						'href' => $title->getLocalURL( array(
								'action' => 'edit',
								'restore' => $revid )
							),
					);

					$links['views'] = array_merge( $head, $neck, $tail );
				}
			}
		}

		return true;
	}

	/**
	 * Handles a rebuild request by rebuilding all secondary storage of the repository.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/WikibaseRebuildData
	 *
	 * @since 0.1
	 *
	 * @param callable $reportMessage
	 *
	 * @return boolean
	 */
	public static function onWikibaseRebuildData( $reportMessage ) {
		$store = StoreFactory::getStore();
		$stores = array_flip( $GLOBALS['wbStores'] );

		$reportMessage( 'Starting rebuild of the Wikibase repository ' . $stores[get_class( $store )] . ' store...' );

		$store->rebuild();

		$reportMessage( "done!\n" );

		return true;
	}

	/**
	 * Deletes all the data stored on the repository.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/WikibaseDeleteData
	 *
	 * @since 0.1
	 *
	 * @param callable $reportMessage
	 *
	 * @return boolean
	 */
	public static function onWikibaseDeleteData( $reportMessage ) {
		$reportMessage( 'Deleting revisions from Data NS...' );

		$dbw = wfGetDB( DB_MASTER );

		$dbw->deleteJoin(
			'revision', 'page',
			'rev_page', 'page_id',
			array( 'page_namespace' => WB_NS_DATA )
		);

		$reportMessage( "done!\n" );

		$reportMessage( 'Deleting pages from Data NS...' );

		$dbw->delete(
			'page',
			array( 'page_namespace' => WB_NS_DATA )
		);

		$reportMessage( "done!\n" );

		$store = StoreFactory::getStore();
		$stores = array_flip( $GLOBALS['wbStores'] );

		$reportMessage( 'Deleting data from the ' . $stores[get_class( $store )] . ' store...' );

		$store->clear();

		$reportMessage( "done!\n" );

		return true;
	}

	/**
	 * Used to append a css class to the body, so the page can be identified as Wikibase item page.
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/OutputPageBodyAttributes
	 *
	 * @since 0.1
	 *
	 * @param \OutputPage $out
	 * @param \Skin $sk
	 * @param array $bodyAttrs
	 *
	 * @return bool
	 */
	public static function onOutputPageBodyAttributes( \OutputPage $out, \Skin $sk, array &$bodyAttrs ) {
		if ( $out->getTitle()->getContentModel() === CONTENT_MODEL_WIKIBASE_ITEM ) {
			// we only add the classes, if there is an actual item and not just an empty Page in the right namespace
			$itemPage = new WikiPage( $out->getTitle() );
			$itemContent = $itemPage->getContent();

			if( $itemContent !== null ) {
				// add class to body so it's clear this is a wb item:
				$bodyAttrs['class'] .= ' wb-itempage';
				// add another class with the ID of the item:
				$bodyAttrs['class'] .= ' wb-itempage-' . $itemContent->getItem()->getId();

				if ( $sk->getRequest()->getCheck( 'diff' ) ) {
					$bodyAttrs['class'] .= ' wb-diffpage';
				}

				if ( $out->getRevisionId() !== $out->getTitle()->getLatestRevID() ) {
					$bodyAttrs['class'] .= ' wb-oldrevpage';
				}

			}
		}
		return true;
	}

	/**
	 * Special page handling where we want to display meaningful link labels instead of just the items ID.
	 * This is only handling special pages right now and gets disabled in normal pages.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LinkBegin
	 *
	 * @param \DummyLinker $skin
	 * @param Title $target
	 * @param string $text
	 * @param array $customAttribs
	 * @param string $query
	 * @param array $options
	 * @param mixed $ret
	 * @return bool true
	 */
	public static function onLinkBegin( $skin, $target, &$html, array &$customAttribs, &$query, &$options, &$ret ) {
		if(
			// if custom link text is given, there is no point in overwriting it
			$html !== null
			// we only want to handle links to data items differently here
			|| $target->getContentModel() !== CONTENT_MODEL_WIKIBASE_ITEM
			// as of MW 1.20 Linker shouldn't support anything but Title anyhow
			|| ! $target instanceof Title
		) {
			return true;
		}

		// $wgTitle is temporarily set to special pages Title in case of special page inclusion! Therefore we can
		// just check whether the page is a special page and if not, disable the behavior.
		global $wgTitle;

		if( $wgTitle === null || !$wgTitle->isSpecialPage() ) {
			// no special page, we don't handle this for now
			// NOTE: If we want to handle this, messages would have to be generated in sites language instead of
			//       users language so they are cache independent.
			return true;
		}

		global $wgLang, $wgOut;

		// The following three vars should all exist, unless there is a failurre
		// somewhere, and then it will fail hard. Better test it now!
		$page = new WikiPage( $target );
		if ( is_null( $page ) ) {
			// Failed, can't continue. This should not happen.
			return true;
		}
		$content = $page->getContent();
		if ( is_null( $content ) ) {
			// Failed, can't continue. This could happen because the content is empty (page doesn't exist),
			// e.g. after item was deleted.
			return true;
		}
		$item = $content->getItem();
		if ( is_null( $item ) ) {
			// Failed, can't continue. This could happen because there is an illegal structure that could
			// not be parsed.
			return true;
		}

		// If this fails we will not find labels and descriptions later,
		// but we will try to get a list of alternate languages. The following
		// uses the user language as a starting point for the fallback chain.
		// It could be argued that the fallbacks should be limited to the user
		// selected languages.
		$lang = $wgLang->getCode();
		static $langStore = array();
		if ( !isset( $langStore[$lang] ) ) {
			$langStore[$lang] = array_merge( array( $lang ), Language::getFallbacksFor( $lang ) );
		}

		// Get the label and description for the first languages on the chain
		// that doesn't fail, use a fallback if everything fails. This could
		// use the user supplied list of acceptable languages as a filter.
		list( $labelCode, $labelText, $labelLang) = $labelTriplet =
			Utils::lookupMultilangText(
				$item->getLabels( $langStore[$lang] ),
				$langStore[$lang],
				array( $wgLang->getCode(), null, $wgLang )
			);
		list( $descriptionCode, $descriptionText, $descriptionLang) = $descriptionTriplet =
			Utils::lookupMultilangText(
				$item->getDescriptions( $langStore[$lang] ),
				$langStore[$lang],
				array( $wgLang->getCode(), null, $wgLang )
			);

		// Go on and construct the link
		$idHtml = Html::openElement( 'span', array( 'class' => 'wb-itemlink-id' ) )
			. wfMessage( 'wikibase-itemlink-id-wrapper', 'q' . $item->getId() )->inContentLanguage()->escaped()
			. Html::closeElement( 'span' );

		$labelHtml = Html::openElement( 'span', array( 'class' => 'wb-itemlink-label', 'lang' => $labelLang->getCode(), 'dir' => $labelLang->getDir() ) )
			. htmlspecialchars( $labelText )
			. Html::closeElement( 'span' );

		$html = Html::openElement( 'span', array( 'class' => 'wb-itemlink' ) )
			. wfMessage( 'wikibase-itemlink' )->rawParams( $labelHtml, $idHtml )->inContentLanguage()->escaped()
			. Html::closeElement( 'span' );

		// Set title attribute for constructed link, and make tricks with the directionality to get it right
		$titleText = ( $labelText !== '' )
			? $labelLang->getDirMark() . $labelText . $wgLang->getDirMark()
			: $target->getPrefixedText();
		$customAttribs[ 'title' ] = ( $descriptionText !== '' ) ?
			wfMessage(
				'wikibase-itemlink-title',
				$titleText,
				$descriptionLang->getDirMark() . $descriptionText . $wgLang->getDirMark()
			)->inContentLanguage()->text() :
			$titleText; // no description, just display the title then

		// add wikibase styles in all cases, so we can format the link properly:
		$wgOut->addModuleStyles( array( 'wikibase.common' ) );

		return true;
	}
}
