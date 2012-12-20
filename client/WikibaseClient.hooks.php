<?php
namespace Wikibase;

/**
 * File defining the hook handlers for the Wikibase Client extension.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 *
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Jens Ohlig
 * @author Daniel Kinzler
 * @author Tobias Gritschacher
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

final class ClientHooks {

	/**
	 * Schema update to set up the needed database tables.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LoadExtensionSchemaUpdates
	 *
	 * @since 0.1
	 *
	 * @param \DatabaseUpdater $updater
	 *
	 * @return bool
	 */
	public static function onSchemaUpdate( \DatabaseUpdater $updater ) {
		wfProfileIn( __METHOD__ );

		$type = $updater->getDB()->getType();

		if ( $type === 'mysql' || $type === 'sqlite' /* || $type === 'postgres' */ ) {
			$extension = $type === 'postgres' ? '.pg.sql' : '.sql';

			if ( Settings::get( 'repoDatabase' ) === null ) {
				// if we don't have direct access to the repo database, set up local caches.

				$updater->addExtensionTable(
					'wbc_entity_cache',
					__DIR__ . '/sql/WikibaseCache' . $extension
				);
			}

			// TODO: re-enable this once we are actually tracking item usage, etc
			/*
			$updater->addExtensionTable(
				'wbc_item_usage',
				__DIR__ . '/sql/WikibaseClient' . $extension
			);
			*/
		}
		else {
			wfWarn( "Database type '$type' is not supported by Wikibase Client." );
		}

		wfProfileOut( __METHOD__ );
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
	 * @return bool
	 */
	public static function registerUnitTests( array &$files ) {
		// @codeCoverageIgnoreStart
		$testFiles = array(
			'MockRepository',
			'includes/LangLinkHandler',

			'includes/CachedEntity',
			'includes/ClientUtils',
			'includes/EntityCacheUpdater',

			'includes/api/ApiClientInfo',

			'includes/store/EntityCacheTable',
			'includes/store/CachingSqlStore',
			'includes/store/DirectSqlStore',
		);

		foreach ( $testFiles as $file ) {
			$files[] = __DIR__ . '/tests/phpunit/' . $file . 'Test.php';
		}

		return true;
		// @codeCoverageIgnoreEnd
	}

	/**
	 * Deletes all the data stored on the repository.
	 *
	 * @since 0.2
	 *
	 * @param callable $reportMessage // takes a string param and echos it
	 *
	 * @return bool
	 */
	public static function onWikibaseDeleteData( $reportMessage ) {
		wfProfileIn( __METHOD__ );

		$store = ClientStoreFactory::getStore();
		$stores = array_flip( $GLOBALS['wgWBClientStores'] );

		$reportMessage( "Deleting data from the " . $stores[get_class( $store )] . " store..." );

		$store->clear();

		// @todo filter by something better than RC_EXTERNAL, in case something else uses that someday
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete(
			'recentchanges',
			array( 'rc_type' => RC_EXTERNAL ),
			__METHOD__
		);

		$reportMessage( "done!\n" );

		wfProfileOut( __METHOD__ );
		return true;
	}

	/**
	 * Rebuilds all the data stored on the repository.
	 * This hook will probably be called manually when the
	 * rebuildAllData script is run on the client.
	 * @todo Be smarter and call this hook from pollForChanges
	 *
	 * @since 0.2
	 *
	 * @param callable $reportMessage // takes a string parameter and echos it
	 *
	 * @return bool
	 */
	public static function onWikibaseRebuildData( $reportMessage ) {
		wfProfileIn( __METHOD__ );

		$store = ClientStoreFactory::getStore();
		$stores = array_flip( $GLOBALS['wgWBClientStores'] );
		$reportMessage( "Rebuilding all data in the " . $stores[get_class( $store )] . " store on the client..." );
		$store->rebuild();
		$changes = ChangesTable::singleton();
		$changes = $changes->select(
			null,
			array(),
			array(),
			__METHOD__
		);
		ChangeHandler::singleton()->handleChanges( iterator_to_array( $changes ) );
		$reportMessage( "done!\n" );

		wfProfileOut( __METHOD__ );
		return true;
	}

	/**
	 * When the poll script finds a new change or set of changes, it will fire
	 * this hook for each change, so it can be handled appropriately.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/WikibasePollHandle
	 *
	 * @since 0.1
	 *
	 * @param Change $change
	 *
	 * @throws \MWException
	 *
	 * @return bool
	 */
	public static function onWikibasePollHandle( Change $change ) {
		wfProfileIn( __METHOD__ );
		wfDebugLog( __CLASS__, __FUNCTION__ . ": handling change #" . $change->getId() );

		if ( ! ( $change instanceof EntityChange ) ) {
			return true;
		}

		/* @var EntityChange $change */

		if ( \SitesTable::singleton()->exists() === false ) {
			throw new \MWException( 'Sites table does not exist, but is required for handling changes.' );
		}

		if ( Settings::get( 'repoDatabase' ) === null ) {
			// no direct access to the repo database, use local cache
			$cacheUpdater = new EntityCacheUpdater();
			$cacheUpdater->handleChange( $change );
		}

		// Invalidate local pages connected to a relevant data item.
		// TODO: handle changes for foreign wikis (push to job queue).
		// TODO: handle other kinds of entities!
		if ( $change->getEntityId()->getEntityType() === Item::ENTITY_TYPE ) {

			$siteGlobalId = Settings::get( 'siteGlobalID' );
			$changeHandler = new ClientChangeHandler( $change );

			$pagesToUpdate = array();

			// if something relevant about the entity changes, update
			// the corresponding local page
			if ( $changeHandler->changeNeedsRendering( $change ) ) {

				$siteLinkTable = ClientStoreFactory::getStore()->newSiteLinkTable();
				$itemId = $change->getEntityId()->getNumericId();

				// @todo: getLinks is a bit ugly, need a getter for a pair of item id + site key
				$siteLinks = $siteLinkTable->getLinks( array( $itemId ), array( $siteGlobalId ) );
				if ( !empty( $siteLinks ) ) {
					$pagesToUpdate[] = $siteLinks[0][1];
				}
			}

			// if an item's sitelinks change, update the old and the new target
			$siteLinkDiff = $change->getSiteLinkDiff();

			$siteLinkDiffOp = isset( $siteLinkDiff[ $siteGlobalId ] )
										? $siteLinkDiff[ $siteGlobalId ] : null;

			if ( $siteLinkDiffOp === null ) {
				// do nothing
			} elseif ( $siteLinkDiffOp instanceof \Diff\DiffOpAdd ) {
				$pagesToUpdate[] = $siteLinkDiffOp->getNewValue();
			} elseif ( $siteLinkDiffOp instanceof \Diff\DiffOpRemove ) {
				$pagesToUpdate[] = $siteLinkDiffOp->getOldValue();
			} elseif ( $siteLinkDiffOp instanceof \Diff\DiffOpChange ) {
				$pagesToUpdate[] = $siteLinkDiffOp->getNewValue();
				$pagesToUpdate[] = $siteLinkDiffOp->getOldValue();
			} else {
				wfWarn( "Unknown change operation: " . get_class( $siteLinkDiffOp )
					. " (" . $siteLinkDiffOp->getType() . ")" );
			}

			wfDebugLog( __CLASS__, __FUNCTION__ . ": pages to update: "
				. str_replace( "\n", ' ', var_export( $pagesToUpdate, true ) ) );

			// purge all relevant pages
			//
			// @todo: instead of rerendering everything, schedule pages for different kinds
			// of update, depending on how the entity was modified. E.g. changes to
			// sitelinks could in theory be handled without re-parsing the page, but
			// would still need to purge the squid cache.
			foreach ( array_unique( $pagesToUpdate ) as $page ) {
				$title = \Title::newFromText( $page );
				if ( in_array( $title->getNamespace(), Settings::get( 'namespaces' ) ) ) {
					self::updatePage( $title, $change, false );
				}
			}
		}

		wfProfileOut( __METHOD__ );
		return true;
	}

	/**
	 * Registers change with recent changes and performs other updates
	 *
	 * @since 0.2
	 *
	 * @param string $title  The Title of the page to update
	 * @param Change $change The Change that caused the update
	 *
	 * @return bool
	 */
	protected static function updatePage( $title, Change $change ) {
		wfProfileIn( __METHOD__ );

		if ( !$title->exists() ) {
			wfProfileOut( __METHOD__ );
			return false;
		}

		wfDebugLog( __CLASS__, __FUNCTION__ . ": purging page " . $titleText );

		$title->invalidateCache();
		$title->purgeSquid();

		if ( Settings::get( 'injectRecentChanges' )  === false ) {
			wfProfileOut( __METHOD__ );
			return true;
		}

		$rcinfo = $change->getMetadata();

		if ( ! is_array( $rcinfo ) ) {
			wfProfileOut( __METHOD__ );
			return false;
		}

		$changeHandler = new ClientChangeHandler( $change );
		$rcinfo['comment'] = $changeHandler->siteLinkComment();

		$fields = $change->getFields(); //@todo: Fixme: add getFields() to the interface, or provide getters!
		$fields['entity_type'] = $change->getEntityType();
		unset( $fields['info'] );

		$params = array(
			'wikibase-repo-change' => array_merge( $fields, $rcinfo )
		);

		$rc = ExternalRecentChange::newFromAttribs( $params, $title );

		// @todo batch these
		wfDebugLog( __CLASS__, __FUNCTION__ . ": saving RC entry for " . $title->getFullText() );
		$rc->save();

		wfProfileOut( __METHOD__ );
		return true;
	}

	/**
	 * Hook for injecting a message on [[Special:MovePage]]
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialMovepageAfterMove
	 *
	 * @since 0.3
	 *
	 * @param \MovePageForm $movePage
	 * @param \Title &$oldTitle
	 * @param \Title &$newTitle
	 *
	 * @return bool
	 */
	public static function onSpecialMovepageAfterMove( \MovePageForm $movePage, \Title &$oldTitle, \Title &$newTitle ) {
		$siteLinkCache = ClientStoreFactory::getStore()->newSiteLinkTable();
		$globalId = Settings::get( 'siteGlobalID' );
		$itemId = $siteLinkCache->getItemIdForLink(
			$globalId,
			$oldTitle->getText()
		);

		if ( $itemId !== false ) {
			$itemByTitle = 'Special:ItemByTitle/' . $globalId . '/' . $oldTitle->getDBkey();
			$itemByTitleLink = ClientUtils::repoArticleUrl( $itemByTitle );
			$out = $movePage->getOutput();
			$out->addModules( 'wikibase.client.page-move' );
			$out->addHTML(
				\Html::rawElement(
					'div',
					array( 'id' => 'wbc-after-page-move',
							'class' => 'plainlinks' ),
					wfMessage( 'wbc-after-page-move', $itemByTitleLink )->parse()
				)
			);
		}
		return true;
	}

	/**
	 * Hook for modifying the query for fetching recent changes
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialRecentChangesQuery
	 *
	 * @since 0.2
	 *
	 * @param &$conds[]
	 * @param &$tables[]
	 * @param &$join_conds[]
	 * @param \FormOptions $opts
	 * @param &$query_options[]
	 * @param &$fields[]
	 *
	 * @return bool
	 */
	public static function onSpecialRecentChangesQuery( array &$conds, array &$tables, array &$join_conds,
		\FormOptions $opts, array &$query_options, array &$fields ) {
		wfProfileIn( __METHOD__ );

		$rcFilterOpts = new RecentChangesFilterOptions( $opts );

		if ( $rcFilterOpts->showWikibaseEdits() === false ) {
			$conds[] = 'rc_type != ' . RC_EXTERNAL;
		}

		wfProfileOut( __METHOD__ );
		return true;
	}

	/**
	 * Hook for formatting recent changes linkes
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/OldChangesListRecentChangesLine
	 *
	 * @since 0.2
	 *
	 * @param \ChangesList $changesList
	 * @param string $s
	 * @param \RecentChange $rc
	 *
	 * @return bool
	 */
	public static function onOldChangesListRecentChangesLine( \ChangesList &$changesList, &$s, \RecentChange $rc ) {
		wfProfileIn( __METHOD__ );

		$rcType = $rc->getAttribute( 'rc_type' );
		if ( $rcType == RC_EXTERNAL ) {
			$params = unserialize( $rc->getAttribute( 'rc_params' ) );
			if ( array_key_exists( 'wikibase-repo-change', $params ) ) {
				$line = ExternalChangesLine::changesLine( $changesList, $rc );
				if ( $line == false ) {
					return false;
				}
				$s = $line;
			}
		}

		wfProfileOut( __METHOD__ );
		return true;
	}

	/**
	 * Modifies watchlist query to include external changes
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialWatchlistQuery
	 *
	 * @since 0.2
	 *
	 * @param array &$conds
	 * @param array &$tables
	 * @param array &$join_conds
	 * @param array &$fields
	 *
	 * @return bool
	 */
	public static function onSpecialWatchlistQuery( array &$conds, array &$tables, array &$join_conds, array &$fields ) {
		wfProfileIn( __METHOD__ );

		$dbr = wfGetDB( DB_SLAVE );

		$newConds = array();
		foreach( $conds as $k => $v ) {
			if ( $v ===  'rc_this_oldid=page_latest OR rc_type=3' ) {
				$where = array(
					'rc_this_oldid' => 'page_latest',
					'rc_type' => array( 3, 5 )
				);
				$newConds[$k] = $dbr->makeList( $where, LIST_OR );
			} else {
				$newConds[$k] = $v;
			}
		}
		$conds = $newConds;

		wfProfileOut( __METHOD__ );
		return true;
	}

	/**
	 * Hook runs after internal parsing
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserAfterParse
	 *
	 * @since 0.1
	 *
	 * @param \Parser $parser
	 * @param string $text
	 * @param \StripState $stripState
	 *
	 * @return bool
	 */
	public static function onParserAfterParse( \Parser &$parser, &$text, \StripState $stripState ) {
		wfProfileIn( __METHOD__ );

		$parserOutput = $parser->getOutput();

		// only run this once, for the article content and not interface stuff
		//FIXME: this also runs for messages in EditPage::showEditTools! Ugh!
		if ( $parser->getOptions()->getInterfaceMessage() ) {
			return true;
		}

		$langLinkHandler = new LangLinkHandler(
			Settings::get( 'siteGlobalID' ),
			Settings::get( 'namespaces' ),
			ClientStoreFactory::getStore()->newSiteLinkTable(),
			\Sites::singleton() );

		$useRepoLinks = $langLinkHandler->useRepoLinks( $parser->getTitle(), $parser->getOutput() );

		if ( $useRepoLinks ) {
			// add links
			$langLinkHandler->addLinksFromRepository( $parser->getTitle(), $parser->getOutput() );
		}

		if ( $useRepoLinks || Settings::get( 'alwaysSort' ) ) {
			// sort links
			SortUtils::sortLinks( $parserOutput->getLanguageLinks() );
		}

		wfProfileOut( __METHOD__ );
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
	 * @return bool
	 */
	public static function onWikibaseDefaultSettings( array &$settings ) {
		global $wgDBname, $wgScriptPath, $wgArticlePath;
		wfProfileIn( __METHOD__ );

		$settings = array_merge(
			$settings,
			array(
				'namespaces' => array( NS_MAIN ),
				'source' => array( 'dir' => __DIR__ . '/tests' ),
				'repoUrl' => '//wikidata.org',
				'repoScriptPath' => $wgScriptPath,
				'repoArticlePath' => $wgArticlePath,
				'sort' => 'code',
				'sortPrepend' => false,
				'alwaysSort' => true,
				'siteGlobalID' => $wgDBname,
				'siteGroup' => 'wikipedia',
				'injectRecentChanges' => true,
				'showExternalRecentChanges' => true,
				'defaultClientStore' => null,
				'repoDatabase' => null, // note: "false" means "local"!
				// default for repo items in main namespace
				'repoNamespaces' => array(
					'wikibase-item' => '',
					'wikibase-property' => 'Property'
				)
			)
		);

		wfProfileOut( __METHOD__ );
		return true;
	}

	/**
	 * Adds css for the edit links sidebar link
	 *
	 * @param \OutputPage $out
	 * @param \Skin $skin
	 *
	 * @since 0.1
	 *
	 * @return bool
	 */
	public static function onBeforePageDisplay( \OutputPage $out, \Skin $skin ) {
		wfProfileIn( __METHOD__ );

		$title = $out->getTitle();

		if ( in_array( $title->getNamespace(), Settings::get( 'namespaces' ) ) ) {
			$out->addModules( 'wikibase.client.init' );
		}

		wfProfileOut( __METHOD__ );
		return true;
	}

	/**
	 * Displays a list of links to pages on the central wiki at the end of the language box.
	 *
	 * @param \Skin $skin
	 * @param \QuickTemplate $template
	 *
	 * @since 0.1
	 *
	 * @return bool
	 */
	public static function onSkinTemplateOutputPageBeforeExec( \Skin &$skin, \QuickTemplate &$template ) {
		wfProfileIn( __METHOD__ );

		if ( empty( $template->data['language_urls'] ) ) {
			wfProfileOut( __METHOD__ );
			return true;
		}

		$title = $skin->getContext()->getTitle();
		if ( in_array( $title->getNamespace(), Settings::get( 'namespaces' ) ) ) {

			$title = $skin->getContext()->getTitle();

			// gets the main part of the title, no underscores used in this db table
			$titleText = $title->getText();

			// main part of title for building link
			$titleLink = $title->getPartialURL();
			$siteId = Settings::get( 'siteGlobalID' );

			$itemId = ClientStoreFactory::getStore()->newSiteLinkTable()->getItemIdForLink( $siteId, $titleText );

			if ( $itemId ) {
				// links to the special page
				$template->data['language_urls'][] = array(
					'href' => ClientUtils::repoArticleUrl( "Special:ItemByTitle/$siteId/$titleLink" ),
					'text' => wfMessage( 'wbc-editlinks' )->text(),
					'title' => wfMessage( 'wbc-editlinkstitle' )->text(),
					'class' => 'wbc-editpage',
				);
			}
		}

		wfProfileOut( __METHOD__ );
		return true;
	}

	/**
	 * Adds a toggle for showing/hiding Wikidata entries in recent changes
	 *
	 * @param \SpecialRecentChanges $special
	 * @param array &$filters
	 *
	 * @return bool
	 */
	public static function onSpecialRecentChangesFilters( \SpecialRecentChanges $special, array &$filters ) {
		$showWikidata = $special->getUser()->getOption( 'rcshowwikidata' );
		$default = $showWikidata ? false : true;
		if ( $special->getUser()->getOption( 'usenewrc' ) === 0 ) {
			$filters['hidewikidata'] = array( 'msg' => 'wbc-rc-hide-wikidata', 'default' => $default );
		}

		return true;
	}

	/**
	 * Adds a preference for showing or hiding Wikidata entries in recent changes
	 *
	 * @param \User $user
	 * @param $preferences[]
	 *
	 * @return bool
	 */
	public static function onGetPreferences( \User $user, array $prefs ) {
		$prefs['rcshowwikidata'] = array(
			'type' => 'toggle',
			'label-message' => 'wbc-rc-show-wikidata-pref',
			'section' => 'rc/advancedrc',
		);

		return true;
	}

}
