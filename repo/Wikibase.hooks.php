<?php

namespace Wikibase;
use Title, Language, User, Revision, WikiPage, EditPage, ContentHandler, Html, MWException;


/**
 * File defining the hook handlers for the Wikibase extension.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Nikola Smolenski
 * @author Daniel Werner
 * @author Michał Łazowik
 */
final class RepoHooks {

	/**
	 * Handler for the SetupAfterCache hook, completing setup of
	 * content and namespace setup.
	 *
	 * @since 0.1
	 *
	 * @note: $wgExtraNamespaces and $wgNamespaceAliases have already been processed at this point
	 *        and should no longer be touched.
	 *
	 * @return boolean
	 * @throws MWException
	 */
	public static function onSetupAfterCache() {
		wfProfileIn( "Wikibase-" . __METHOD__ );
		global $wgNamespaceContentModels;

		$namespaces = Settings::get( 'entityNamespaces' );

		if ( empty( $namespaces ) ) {
			wfProfileOut( "Wikibase-" . __METHOD__ );
			throw new MWException( 'Wikibase: Incomplete configuration: '
				. '$wgWBSettings["entityNamespaces"] has to be set to an array mapping content model IDs to namespace IDs. '
				. 'See ExampleSettings.php for details and examples.');
		}

		foreach ( $namespaces as $model => $ns ) {
			if ( !isset( $wgNamespaceContentModels[$ns] ) ) {
				$wgNamespaceContentModels[$ns] = $model;
			}
		}

		wfProfileOut( "Wikibase-" . __METHOD__ );
		return true;
	}

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
		$type = $updater->getDB()->getType();

		if ( $type === 'mysql' || $type === 'sqlite' /* || $type === 'postgres' */ ) {
			$extension = $type === 'postgres' ? '.pg.sql' : '.sql';

			$updater->addExtensionTable(
				'wb_changes',
				__DIR__ . '/sql/changes' . $extension
			);

			if ( $type === 'mysql' && !$updater->updateRowExists( 'ChangeChangeObjectId.sql' ) ) {
				$updater->addExtensionUpdate( array(
					'applyPatch',
					__DIR__ . '/sql/ChangeChangeObjectId.sql',
					true
				) );

				$updater->insertUpdateRow( 'ChangeChangeObjectId.sql' );
			}
		}
		else {
			wfWarn( "Database type '$type' is not supported by the Wikibase repository." );
		}

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
			'Autocomment',
			'ItemMove',
			'ItemContentDiffView',
			'ItemMove',
			'ItemView',
			'EditEntity',

			'actions/EditEntityAction',

			'api/ApiBotEdit',
			'api/ApiEditPage',
			'api/ApiGetEntities',
			'api/ApiLabel',
			'api/ApiDescription',
			'api/ApiLinkTitles',
			'api/ApiPermissions',
			'api/ApiSetAliases',
			'api/ApiEditEntity',
			'api/ApiSetSiteLink',

			'content/EntityContentFactory',
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

			'store/IdGenerator',
			'store/StoreFactory',
			'store/Store',
			'store/TermCache',

			'store/sql/SqlIdGenerator',
			'store/sql/TermSqlCache',

			'updates/ItemDeletionUpdate',
			'updates/ItemModificationUpdate',
		);

		foreach ( $testFiles as $file ) {
			$files[] = __DIR__ . '/tests/phpunit/includes/' . $file . 'Test.php';
		}

		return true;
		// @codeCoverageIgnoreEnd
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
	public static function registerExperimentalUnitTests( array &$files ) {
		// @codeCoverageIgnoreStart
		$testFiles = array(
			'specials/SpecialEntityData',

			'api/ApiCreateClaim',
			'api/ApiGetClaims',
			'api/ApiRemoveClaims',
			'api/ApiSetClaimValue',
			'api/ApiSetReference',
			'api/RemoveReferences',
			'api/SetStatementRank',
		);

		foreach ( $testFiles as $file ) {
			$files[] = __DIR__ . '/tests/phpunit/includes/' . $file . 'Test.php';
		}

		return true;
		// @codeCoverageIgnoreEnd
	}

	/**
	 * Handler for the NamespaceIsMovable hook.
	 *
	 * Implemented to prevent moving pages that are in an entity namespace.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/NamespaceIsMovable
	 *
	 * @since 0.1
	 *
	 * @param integer $ns Namespace ID
	 * @param boolean $movable
	 *
	 * @return boolean
	 */
	public static function onNamespaceIsMovable( $ns, &$movable ) {
		wfProfileIn( "Wikibase-" . __METHOD__ );

		if ( Utils::isEntityNamespace( $ns ) ) {
			$movable = false;
		}

		wfProfileOut( "Wikibase-" . __METHOD__ );
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
		wfProfileIn( "Wikibase-" . __METHOD__ );

		if ( EntityContentFactory::singleton()->isEntityContentModel( $article->getContent()->getModel() ) ) {
			/**
			 * @var $newEntity Entity
			 */
			$newEntity = $article->getContent()->getEntity();

			$parent = is_null( $revision->getParentId() )
				? null : Revision::newFromId( $revision->getParentId() );

			$change = EntityChange::newFromUpdate(
				$parent ? EntityChange::UPDATE : EntityChange::ADD,
				$parent ? $parent->getContent()->getEntity() : null,
				$newEntity
			);

			$change->setFields( array(
				'revision_id' => $revision->getId(),
				'user_id' => $user->getId(),
				'object_id' => $newEntity->getId()->getPrefixedId(),
				'time' => $revision->getTimestamp(),
			) );

			ChangeNotifier::singleton()->handleChange( $change );
		}

		wfProfileOut( "Wikibase-" . __METHOD__ );
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
	 * @param \Content $content
	 * @param \LogEntryBase $logEntry
	 *
	 * @throws MWException
	 *
	 * @return boolean
	 */
	public static function onArticleDeleteComplete( WikiPage $wikiPage, User $user, $reason, $id,
		\Content $content = null, \LogEntryBase $logEntry = null
	) {
		wfProfileIn( "Wikibase-" . __METHOD__ );

		$entityContentFactory = EntityContentFactory::singleton();

		// Bail out if we are not looking at an entity
		if ( !$content || !$entityContentFactory->isEntityContentModel( $wikiPage->getContentModel() ) ) {
			wfProfileOut( "Wikibase-" . __METHOD__ );
			return true;
		}

		/**
		 * @var Entity $entity
		 */
		$entity = $content->getEntity();

		$change = EntityChange::newFromUpdate( EntityChange::REMOVE, $entity, null, array(
			'revision_id' => 0, // there's no current revision
			'user_id' => $user->getId(),
			'object_id' => $entity->getId()->getPrefixedId(),
			'time' => $logEntry->getTimestamp(),
		) );

		$change->setMetadataFromUser( $user );

		ChangeNotifier::singleton()->handleChange( $change );

		wfProfileOut( "Wikibase-" . __METHOD__ );
		return true;
	}

	/**
	 * Handle changes for undeletions
	 *
	 * @since 0.2
	 *
	 * @param Title $title
	 * @param bool $created
	 * @param string $comment
	 *
	 * @return bool
	 */
	public static function onArticleUndelete( Title $title, $created, $comment ) {
		wfProfileIn( "Wikibase-" . __METHOD__ );

		$entityContentFactory = EntityContentFactory::singleton();

		// Bail out if we are not looking at an entity
		if ( !$entityContentFactory->isEntityContentModel( $title->getContentModel() ) ) {
			return true;
		}

		$revId = $title->getLatestRevID();
		$content = $entityContentFactory->getFromRevision( $revId );

		if ( $content ) {
			StoreFactory::getStore()->newEntityPerPage()->addEntityContent( $content );

			$entity = $content->getEntity();

			$rev = Revision::newFromId( $revId );

			$userId = $rev->getUser();
			$change = EntityChange::newFromUpdate( EntityChange::RESTORE, null, $entity, array(
				// TODO: Use timestamp of log entry, but needs core change.
				// This hook is called before the log entry is created.
				'revision_id' => $revId,
				'user_id' => $userId,
				'object_id' => $entity->getId()->getPrefixedId(),
				'time' => wfTimestamp( TS_MW, wfTimestampNow() )
			) );

			$user = User::newFromId( $userId );
			$change->setMetadataFromUser( $user );

			ChangeNotifier::singleton()->handleChange( $change );
		}

		wfProfileOut( "Wikibase-" . __METHOD__ );
		return true;
	}

	/**
	 * TODO: Add some stuff? Seems to be changes propagation...
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/RecentChange_save
	 *
	 * @since ?
	 *
	 * @param $rc RecentChange
	 * @return bool
	 */
	public static function onRecentChangeSave( $rc ) {
		if ( $rc->getAttribute( 'rc_log_type' ) === null ) {
			$change = ChangesTable::singleton()->selectRow(
				null,
				array( 'revision_id' => $rc->getAttribute( 'rc_this_oldid' ) )
			);

			if ( $change ) {
				$change->setMetadataFromRC( $rc );
				$change->save();
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
		wfProfileIn( "Wikibase-" . __METHOD__ );

		$preferences['wb-languages'] = array(
			'type' => 'multiselect',
			'usecheckboxes' => false,
			'label-message' => 'wikibase-setting-languages',
			'options' => $preferences['language']['options'], // all languages available in 'language' selector
			'section' => 'personal/i18n',
			'prefix' => 'wb-languages-',
		);

		wfProfileOut( "Wikibase-" . __METHOD__ );
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
		wfProfileIn( "Wikibase-" . __METHOD__ );

		// pre-select default language in the list of fallback languages
		$defaultLang = $defaultOptions['language'];
		$defaultOptions[ 'wb-languages-' . $defaultLang ] = 1;

		wfProfileOut( "Wikibase-" . __METHOD__ );
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
				// Defaults to turn on deletion of empty items
				// set to true will always delete empty items
				'apiDeleteEmpty' => false,

				// Set API in debug mode
				// do not turn on in production!
				'apiInDebug' => false,

				// Additional settings for API when debugging is on to
				// facilitate testing.
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

				'idBlacklist' => array(
					1,
					23,
					42,
					1337,
					9001,
					31337,
					720101010,
				),

				// Enable to make TermCache work without the term_search_key field,
				// for sites that can not easily roll out schema changes on large tables.
				// This means that all searches will use exact matching
				// (depending on the database's collation).
				'withoutTermSearchKey' => false,
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
		wfProfileIn( "Wikibase-" . __METHOD__ );

		$article = $history->getArticle();
		$rev = new Revision( $row );

		if ( EntityContentFactory::singleton()->isEntityContentModel( $history->getTitle()->getContentModel() )
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

		wfProfileOut( "Wikibase-" . __METHOD__ );
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
		wfProfileIn( "Wikibase-" . __METHOD__ );

		$title = $sktemplate->getTitle();
		$request = $sktemplate->getRequest();

		if ( EntityContentFactory::singleton()->isEntityContentModel( $title->getContentModel() ) ) {
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

		wfProfileOut( "Wikibase-" . __METHOD__ );
		return true;
	}

	/**
	 * Handles a rebuild request by rebuilding all secondary storage of the repository.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/WikibaseRebuildData
	 *
	 * @since 0.1
	 *
	 * @param callable $reportMessage // takes a string param and echos it
	 *
	 * @return boolean
	 */
	public static function onWikibaseRebuildData( $reportMessage ) {
		wfProfileIn( "Wikibase-" . __METHOD__ );

		$store = StoreFactory::getStore();
		$stores = array_flip( $GLOBALS['wgWBStores'] );

		$reportMessage( 'Starting rebuild of the Wikibase repository ' . $stores[get_class( $store )] . ' store...' );

		$store->rebuild();

		$reportMessage( "done!\n" );

		wfProfileOut( "Wikibase-" . __METHOD__ );
		return true;
	}

	/**
	 * Deletes all the data stored on the repository.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/WikibaseDeleteData
	 *
	 * @since 0.1
	 *
	 * @param callable $reportMessage // takes a string param and echos it
	 *
	 * @return boolean
	 */
	public static function onWikibaseDeleteData( $reportMessage ) {
		wfProfileIn( "Wikibase-" . __METHOD__ );

		$reportMessage( 'Deleting data from changes table...' );

		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete( 'wb_changes', '*', __METHOD__ );

		$reportMessage( "done!\n" );

		$reportMessage( 'Deleting revisions from Data NS...' );

		$namespaceList = $dbw->makeList(  Utils::getEntityNamespaces(), LIST_COMMA );

		$dbw->deleteJoin(
			'revision', 'page',
			'rev_page', 'page_id',
			array( 'page_namespace IN ( ' . $namespaceList . ')' )
		);

		$reportMessage( "done!\n" );

		$reportMessage( 'Deleting pages from Data NS...' );

		$dbw->delete(
			'page',
			array( 'page_namespace IN ( ' . $namespaceList . ')' )
		);

		$reportMessage( "done!\n" );

		$store = StoreFactory::getStore();
		$stores = array_flip( $GLOBALS['wgWBStores'] );

		$reportMessage( 'Deleting data from the ' . $stores[get_class( $store )] . ' store...' );

		$store->clear();

		$reportMessage( "done!\n" );

		wfProfileOut( "Wikibase-" . __METHOD__ );
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
		wfProfileIn( "Wikibase-" . __METHOD__ );

		if ( EntityContentFactory::singleton()->isEntityContentModel( $out->getTitle()->getContentModel() ) ) {
			// we only add the classes, if there is an actual item and not just an empty Page in the right namespace
			$entityPage = new WikiPage( $out->getTitle() );
			$entityContent = $entityPage->getContent();

			if( $entityContent !== null ) {
				// TODO: preg_replace kind of ridiculous here, should probably change the ENTITY_TYPE constants instead
				$entityType = preg_replace( '/^wikibase-/i', '', $entityContent->getEntity()->getType() );

				// add class to body so it's clear this is a wb item:
				$bodyAttrs['class'] .= " wb-entitypage wb-{$entityType}page";
				// add another class with the ID of the item:
				$bodyAttrs['class'] .= " wb-{$entityType}page-{$entityContent->getEntity()->getId()->getPrefixedId()}";

				if ( $sk->getRequest()->getCheck( 'diff' ) ) {
					$bodyAttrs['class'] .= ' wb-diffpage';
				}

				if ( $out->getRevisionId() !== $out->getTitle()->getLatestRevID() ) {
					$bodyAttrs['class'] .= ' wb-oldrevpage';
				}
			}
		}

		wfProfileOut( "Wikibase-" . __METHOD__ );
		return true;
	}

	/**
	 * Special page handling where we want to display meaningful link labels instead of just the items ID.
	 * This is only handling special pages right now and gets disabled in normal pages.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LinkBegin
	 *
	 * @param \DummyLinker $skin
	 * @param Title $target
	 * @param string $html
	 * @param array $customAttribs
	 * @param string $query
	 * @param array $options
	 * @param mixed $ret
	 * @return bool true
	 */
	public static function onLinkBegin( $skin, $target, &$html, array &$customAttribs, &$query, &$options, &$ret ) {
		wfProfileIn( "Wikibase-" . __METHOD__ );

		//NOTE: the model returned by Title::getContentModel() is not reliable, see bug 37209
		$model = $target->getContentModel();
		$entityModels = EntityContentFactory::singleton()->getEntityContentModels();


		// we only want to handle links to Wikibase entities differently here
		if ( !in_array( $model, $entityModels ) ) {
			wfProfileOut( "Wikibase-" . __METHOD__ );
			return true;
		}

		// if custom link text is given, there is no point in overwriting it
		// but not if it is similar to the plain title
		if ( $html !== null && $target->getFullText() !== $html ) {
			wfProfileOut( "Wikibase-" . __METHOD__ );
			return true;
		}

		global $wgTitle;

		// $wgTitle is temporarily set to special pages Title in case of special page inclusion! Therefore we can
		// just check whether the page is a special page and if not, disable the behavior.
		if( $wgTitle === null || !$wgTitle->isSpecialPage() ) {
			// no special page, we don't handle this for now
			// NOTE: If we want to handle this, messages would have to be generated in sites language instead of
			//       users language so they are cache independent.
			wfProfileOut( "Wikibase-" . __METHOD__ );
			return true;
		}

		global $wgLang, $wgOut;

		// The following three vars should all exist, unless there is a failurre
		// somewhere, and then it will fail hard. Better test it now!
		$page = new WikiPage( $target );
		if ( is_null( $page ) ) {
			// Failed, can't continue. This should not happen.
			wfProfileOut( "Wikibase-" . __METHOD__ );
			return true;
		}
		$content = $page->getContent();
		if ( is_null( $content ) || !( $content instanceof EntityContent ) ) {
			// Failed, can't continue. This could happen because the content is empty (page doesn't exist),
			// e.g. after item was deleted.

			// Due to bug 37209, we may also get non-entity content here, despite checking
			// Title::getContentModel up front.
			wfProfileOut( "Wikibase-" . __METHOD__ );
			return true;
		}
		$entity = $content->getEntity();
		if ( is_null( $entity ) ) {
			// Failed, can't continue. This could happen because there is an illegal structure that could
			// not be parsed.
			wfProfileOut( "Wikibase-" . __METHOD__ );
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
		list( , $labelText, $labelLang) = $labelTriplet =
			Utils::lookupMultilangText(
				$entity->getLabels( $langStore[$lang] ),
				$langStore[$lang],
				array( $wgLang->getCode(), null, $wgLang )
			);
		list( , $descriptionText, $descriptionLang) = $descriptionTriplet =
			Utils::lookupMultilangText(
				$entity->getDescriptions( $langStore[$lang] ),
				$langStore[$lang],
				array( $wgLang->getCode(), null, $wgLang )
			);

		// Go on and construct the link
		$idHtml = Html::openElement( 'span', array( 'class' => 'wb-itemlink-id' ) )
			. wfMessage( 'wikibase-itemlink-id-wrapper', $target->getText() )->inContentLanguage()->escaped()
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

		wfProfileOut( "Wikibase-" . __METHOD__ );
		return true;
	}

	/**
	 * Handler for the ApiCheckCanExecute hook in ApiMain.
	 *
	 * This implementation causes the execution of ApiEditPage (action=edit) to fail
	 * for all namespaces reserved for Wikibase entities. This prevents direct text-level editing
	 * of structured data, and it also prevents other types of content being created in these
	 * namespaces.
	 *
	 * @param \ApiBase $module The API module being called
	 * @param User    $user   The user calling the API
	 * @param array|string|null   $message Output-parameter holding for the message the call should fail with.
	 *                            This can be a message key or an array as expected by ApiBase::dieUsageMsg().
	 *
	 * @return bool true to continue execution, false to abort and with $message as an error message.
	 */
	public static function onApiCheckCanExecute( \ApiBase $module, User $user, &$message ) {
		wfProfileIn( "Wikibase-" . __METHOD__ );

		if ( $module instanceof \ApiEditPage ) {
			$params = $module->extractRequestParams();
			$pageObj = $module->getTitleOrPageId( $params );
			$namespace = $pageObj->getTitle()->getNamespace();

			foreach ( EntityContentFactory::singleton()->getEntityContentModels() as $model ) {
				/**
				 * @var EntityHandler $handler
				 */
				$handler = ContentHandler::getForModelID( $model );

				if ( $handler->getEntityNamespace() == $namespace ) {
					// trying to use ApiEditPage on an entity namespace - just fail
					$message = array(
						'wikibase-no-direct-editing',
						$pageObj->getTitle()->getNsText()
					);

					wfProfileOut( "Wikibase-" . __METHOD__ );
					return false;
				}
			}
		}

		wfProfileOut( "Wikibase-" . __METHOD__ );
		return true;
	}

	/**
	 * Format the output when the search result contains entities
	 *
	 * @since 0.3
	 *
	 * @param SpecialSearch $searchPage
	 * @param SearchResult $result
	 * @param $terms
	 * @param &$link
	 * @param &$redirect
	 * @param &$section
	 * @param &$extract,
	 * @param &$score
	 * @param &$size
	 * @param &$date
	 * @param &$related
	 * @param &$html
	 *
	 * @return bool
	 */
	public static function onShowSearchHit( \SpecialSearch $searchPage, \SearchResult $result, $terms,
		&$link, &$redirect, &$section, &$extract,
		&$score, &$size, &$date, &$related,
		&$html
	) {
		wfProfileIn( "Wikibase-" . __METHOD__ );

		$model = $result->getTitle()->getContentModel();

		if ( EntityContentFactory::singleton()->isEntityContentModel( $model ) ) {
			$lang = $searchPage->getLanguage();
			$page = WikiPage::factory( $result->getTitle() );

			/* @var EntityContent $content */
			$content = $page->getContent();

			if ( $content ) {
				$entity = $content->getEntity();
				$description = $entity->getDescription( $lang->getCode() ); // TODO: language fallback!

				if ( $description !== false && $description !== '' ) {
					$attr = array( 'class' => 'wb-itemlink-description' );
					$link .= wfMessage( 'colon-separator' )->text();
					$link .= Html::element( 'span', $attr, $description );
				}
			}

			$extract = ''; // TODO: set this to something useful.
		}

		wfProfileOut( "Wikibase-" . __METHOD__ );
		return true;
	}

}
