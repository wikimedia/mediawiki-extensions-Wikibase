<?php
namespace Wikibase;

use \Wikibase\Client\WikibaseClient;

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
 * @author Marius Hoch < hoo@online.de >
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
			'includes/api/ApiClientInfo',

			'includes/parserhooks/PropertyParserFunction',

			'includes/specials/SpecialUnconnectedPages',

			'includes/store/EntityCacheTable',

			'includes/CachedEntity',
			'includes/ChangeHandler',
			'includes/EntityCacheUpdater',
			'includes/LangLinkHandler',
			'includes/RepoLinker',
			'includes/WikibaseClient',
			'includes/UpdateRepoOnMove',
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

		$store = WikibaseClient::getDefaultInstance()->getStore();
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
	 *
	 * @since 0.2
	 *
	 * @param callable $reportMessage // takes a string parameter and echos it
	 *
	 * @return bool
	 */
	public static function onWikibaseRebuildData( $reportMessage ) {
		wfProfileIn( __METHOD__ );

		$store = WikibaseClient::getDefaultInstance()->getStore();
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
		$site = new \MediaWikiSite();
		$site->setGlobalId( Settings::get( 'siteGlobalID' ) );

		$siteLinkLookup = WikibaseClient::getDefaultInstance()->getStore()->getSiteLinkTable();
		$entityId = $siteLinkLookup->getEntityIdForSiteLink(
			new SiteLink( $site, $oldTitle->getFullText() )
		);

		if ( !$entityId ) {
			return true;
		}
		// Create a repo link directly to the item
		// We can't use Special:ItemByTitle here as the item might have already been updated.
		$repoLinker = WikibaseClient::getDefaultInstance()->newRepoLinker();
		$idFormatter = WikibaseClient::getDefaultInstance()->getEntityIdFormatter();
		$idString = $idFormatter->format( $entityId );
		$itemLink = $repoLinker->repoItemUrl( $entityId );

		if ( isset( $newTitle->wikibasePushedMoveToRepo ) ) {
			// We're going to update the item using the repo job queue \o/
			$msg = 'wikibase-after-page-move-queued';
		} else {
			// The user has to update the item per hand for some reason
			$msg = 'wikibase-after-page-move';
		}

		$out = $movePage->getOutput();
		$out->addModules( 'wikibase.client.page-move' );
		$out->addHTML(
			\Html::rawElement(
				'div',
				array( 'id' => 'wbc-after-page-move',
						'class' => 'plainlinks' ),
				wfMessage( $msg, $itemLink )->parse()
			)
		);
		return true;
	}

	/**
	 * External library for Scribunto
	 *
	 * @since 0.4
	 *
	 * @param $engine
	 * @param array $extraLibraries
	 * @return bool
	 */
	public static function onScribuntoExternalLibraries ( $engine, array &$extraLibraries ) {
		if ( Settings::get( 'allowDataTransclusion' ) === true ) {
			$extraLibraries['mw.wikibase'] = 'Scribunto_LuaWikibaseLibrary';
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
	public static function onOldChangesListRecentChangesLine( \ChangesList &$changesList, &$s,
		\RecentChange $rc, &$classes = array() ) {

		wfProfileIn( __METHOD__ );

		$rcType = $rc->getAttribute( 'rc_type' );
		if ( $rcType == RC_EXTERNAL ) {
			$params = unserialize( $rc->getAttribute( 'rc_params' ) );

			if ( !is_array( $params ) ) {
				$varType = is_object( $params ) ? get_class( $params ) : gettype( $params );
				trigger_error( __CLASS__ . ' : $rc_params is not unserialized correctly.  It has '
					. 'been returned as ' . $varType, E_USER_WARNING );
				return false;
			}

			if ( array_key_exists( 'wikibase-repo-change', $params ) ) {
				$line = ExternalChangesLine::changesLine( $changesList, $rc );
				if ( $line == false ) {
					return false;
				}

				$classes[] = 'wikibase-edit';
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
	 * @param array $values
	 *
	 * @return bool
	 */
	public static function onSpecialWatchlistQuery( array &$conds, array &$tables, array &$join_conds, array &$fields, array $values = array() ) {
		global $wgRequest, $wgUser;

		wfProfileIn( __METHOD__ );

		if (
			// Don't act on activated enhanced watchlist
			$wgRequest->getBool( 'enhanced', $wgUser->getOption( 'usenewrc' ) ) === false &&
			// Or in case the user disabled it
			$values['hideWikibase'] === 0
		) {
			$dbr = wfGetDB( DB_SLAVE );

			$newConds = array();
			foreach( $conds as $k => $v ) {
				if ( $v ===  'rc_this_oldid=page_latest OR rc_type=3' ) {
					$where = array(
						'rc_this_oldid=page_latest',
						'rc_type' => array( 3, 5 )
					);
					$newConds[$k] = $dbr->makeList( $where, LIST_OR );
				} else {
					$newConds[$k] = $v;
				}
			}
			$conds = $newConds;
		} else {
			$conds[] = 'rc_type != 5';
		}

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
			wfProfileOut( __METHOD__ );
			return true;
		}

		$langLinkHandler = new LangLinkHandler(
			Settings::get( 'siteGlobalID' ),
			Settings::get( 'namespaces' ),
			Settings::get( 'excludeNamespaces' ),
			WikibaseClient::getDefaultInstance()->getStore()->getSiteLinkTable(),
			\Sites::singleton() );

		$useRepoLinks = $langLinkHandler->useRepoLinks( $parser->getTitle(), $parser->getOutput() );

		if ( $useRepoLinks ) {
			// add links
			$langLinkHandler->addLinksFromRepository( $parser->getTitle(), $parser->getOutput() );
		}

		$langLinkHandler->updateItemIdProperty( $parser->getTitle(), $parser->getOutput() );

		if ( $useRepoLinks || Settings::get( 'alwaysSort' ) ) {
			// sort links
			$interwikiSorter = new InterwikiSorter(
				Settings::get( 'sort' ),
				Settings::get( 'interwikiSortOrders' ),
				Settings::get( 'sortPrepend' )
			);
			$interwikiLinks = $parserOutput->getLanguageLinks();
			$sortedLinks = $interwikiSorter->sortLinks( $interwikiLinks );
			$parserOutput->setLanguageLinks( $sortedLinks );
		}

		wfProfileOut( __METHOD__ );
		return true;
	}

	/**
	 * Adds css for the edit links sidebar link or JS to create a new item
	 * or to link with an existing one.
	 *
	 * @param \OutputPage &$out
	 * @param \Skin &$skin
	 *
	 * @since 0.1
	 *
	 * @return bool
	 */
	public static function onBeforePageDisplay( \OutputPage &$out, \Skin &$skin ) {
		wfProfileIn( __METHOD__ );

		$title = $out->getTitle();
		$user = $skin->getContext()->getUser();

		$namespaceChecker = new NamespaceChecker(
			Settings::get( 'excludeNamespaces' ),
			Settings::get( 'namespaces' )
		);

		if ( $namespaceChecker->isWikibaseEnabled( $title->getNamespace() ) ) {
			$out->addModules( 'wikibase.client.init' );

			if ( !$out->getLanguageLinks() && \Action::getActionName( $skin->getContext() ) === 'view' && $title->exists() ) {
				// Module with the sole purpose to hide #p-lang
				// Needed as we can't do that in the regular CSS nor in JavaScript
				// (as that only runs after the element initially appeared).
				$out->addModules( 'wikibase.client.nolanglinks' );

				if ( Settings::get( 'enableSiteLinkWidget' ) === true && $user->isLoggedIn() === true ) {
					// Add the JavaScript which lazy-loads the link item widget (needed as jquery.wikibase.linkitem has pretty heavy dependencies)
					$out->addModules( 'wikibase.client.linkitem.init' );
				}

			}
		}

		wfProfileOut( __METHOD__ );
		return true;
	}

	/**
	 * Add output page property if repo links are suppressed, and property for item id
	 *
	 * @since 0.4
	 *
	 * @param \OutputPage &$out
	 * @param \ParserOutput $pout
	 *
	 * @return bool
	 */
	public static function onOutputPageParserOutput( \OutputPage &$out, \ParserOutput $pout ) {
		$langLinkHandler = new LangLinkHandler(
			Settings::get( 'siteGlobalID' ),
			Settings::get( 'namespaces' ),
			Settings::get( 'excludeNamespaces' ),
			WikibaseClient::getDefaultInstance()->getStore()->getSiteLinkTable(),
			\Sites::singleton() );

		$noExternalLangLinks = $langLinkHandler->getNoExternalLangLinks( $pout );

		if ( $noExternalLangLinks !== array() ) {
			$out->setProperty( 'noexternallanglinks', $noExternalLangLinks );
		}

		$itemId = $pout->getProperty( 'wikibase_item' );

		if ( $itemId !== false ) {
			$out->setProperty( 'wikibase_item', $itemId );
		}

		return true;
	}

	/**
	 * Displays a list of links to pages on the central wiki at the end of the language box.
	 *
	 * @since 0.1
	 *
	 * @param \Skin $skin
	 * @param \QuickTemplate $template
	 *
	 * @return bool
	 */
	public static function onSkinTemplateOutputPageBeforeExec( \Skin &$skin, \QuickTemplate &$template ) {
		wfProfileIn( __METHOD__ );

		if ( \Action::getActionName( $skin->getContext() ) !== 'view' ) {
			wfProfileOut( __METHOD__ );
			return true;
		}

		$title = $skin->getContext()->getTitle();
		$namespaceChecker = new NamespaceChecker(
			Settings::get( 'excludeNamespaces' ),
			Settings::get( 'namespaces' )
		);

		if ( $title->exists() && $namespaceChecker->isWikibaseEnabled( $title->getNamespace() ) ) {
			// if property is not set, these will return null
			$prefixedId = $skin->getOutput()->getProperty( 'wikibase_item' );
			$noExternalLangLinks = $skin->getOutput()->getProperty( 'noexternallanglinks' );

			// @todo: may want a data link somewhere, even if the links are suppressed
			if ( $noExternalLangLinks === null || !in_array( '*', $noExternalLangLinks ) ) {
				if ( $prefixedId !== null ) {
					$entityId = EntityId::newFromPrefixedId( $prefixedId );

					// should not happen but just in case
					if ( $entityId === null ) {
						wfWarn( 'Wikibase $entityId is is set as output page property but is not valid.' );
						wfProfileOut( __METHOD__ );
						return true;
					}

					$repoLinker = WikibaseClient::getDefaultInstance()->newRepoLinker();

					// links to the associated item on the repo
					$template->data['language_urls'][] = array(
						'href' => $repoLinker->repoItemUrl( $entityId ) . '#sitelinks',
						'text' => wfMessage( 'wikibase-editlinks' )->text(),
						'title' => wfMessage( 'wikibase-editlinkstitle' )->text(),
						'class' => 'wbc-editpage',
					);
				} else {
					// Placeholder in case the page doesn't have any langlinks yet
					// self::onBeforePageDisplay adds the JavaScript module which will overwrite this with a link
					// We can leave this here in case people want to use it for gadgets, css or whatnot,
					// even if the widget is not enabled.
					$template->data['language_urls'][] = array(
						'text' => '',
						'id' => 'wbc-linkToItem',
						'class' => 'wbc-editpage wbc-nolanglinks',
					);
				}
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
		$context = $special->getContext();

		if ( $context->getRequest()->getBool( 'enhanced', $context->getUser()->getOption( 'usenewrc' ) ) === false ) {
			$showWikidata = $special->getUser()->getOption( 'rcshowwikidata' );
			$default = $showWikidata ? false : true;
			if ( $context->getUser()->getOption( 'usenewrc' ) === 0 ) {
				$filters['hidewikidata'] = array( 'msg' => 'wikibase-rc-hide-wikidata', 'default' => $default );
			}
		}

		return true;
	}

	/**
	 * Adds a preference for showing or hiding Wikidata entries in recent changes
	 *
	 * @param \User $user
	 * @param &$prefs[]
	 *
	 * @return bool
	 */
	public static function onGetPreferences( \User $user, array &$prefs ) {
		$prefs['rcshowwikidata'] = array(
			'type' => 'toggle',
			'label-message' => 'wikibase-rc-show-wikidata-pref',
			'section' => 'rc/advancedrc',
		);

		$prefs['wlshowwikibase'] = array(
			'type' => 'toggle',
			'label-message' => 'wikibase-watchlist-show-changes-pref',
			'section' => 'watchlist/advancedwatchlist',
		);

		return true;
	}

	/**
	 * Register the parser functions.
	 *
	 * @param $parser \Parser
	 *
	 * @return bool
	 */
	public static function onParserFirstCallInit( &$parser ) {
		$parser->setFunctionHook( 'noexternallanglinks', '\Wikibase\NoLangLinkHandler::handle', SFH_NO_HASH );

		if ( Settings::get( 'allowDataTransclusion' ) === true ) {
			$parser->setFunctionHook( 'property', array( '\Wikibase\PropertyParserFunction', 'render' ) );
		}

		return true;
	}

	/**
	 * Register the magic word.
	 */
	public static function onMagicWordwgVariableIDs( &$aCustomVariableIds ) {
		$aCustomVariableIds[] = 'noexternallanglinks';
		return true;
	}

	/**
	 * Apply the magic word.
	 */
	public static function onParserGetVariableValueSwitch( &$parser, &$cache, &$magicWordId, &$ret ) {
		if( $magicWordId == 'noexternallanglinks' ) {
			NoLangLinkHandler::handle( $parser, '*' );
		}

		return true;
	}

	/**
	 * Modifies watchlist options to show a toggle for Wikibase changes
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialWatchlistFilters
	 *
	 * @since 0.4
	 *
	 * @param SpecialWatchlist $special
	 * @param array $filters
	 *
	 * @return bool
	 */
	public static function onSpecialWatchlistFilters( $special, &$filters ) {
		$user = $special->getContext()->getUser();

		if ( $special->getContext()->getRequest()->getBool( 'enhanced', $user->getOption( 'usenewrc' ) ) === false ) {
			// Allow toggling wikibase changes in case the enhanced watchlist is disabled
			$filters['hideWikibase'] = array(
				'msg' => 'wikibase-rc-hide-wikidata',
				'default' => !$user->getBoolOption( 'wlshowwikibase' )
			);
		}
		return true;
	}

	/**
	 * Adds the Entity ID of the corresponding Wikidata item in action=info
	 *
	 * @param IContextSource $context
	 * @param array $pageInfo
	 *
	 * @return bool
	 */
	public static function onInfoAction( $context, array &$pageInfo ) {
		// Check if wikibase namespace is enabled
		$title = $context->getTitle();
		$namespaceChecker = new NamespaceChecker(
			Settings::get( 'excludeNamespaces' ),
			Settings::get( 'namespaces' )
		);

		if ( $title->exists() && $namespaceChecker->isWikibaseEnabled( $title->getNamespace() ) ) {

			$site = \MediaWikiSite::newFromGlobalId( Settings::get( 'siteGlobalID' ) );

			$siteLinkLookup = WikibaseClient::getDefaultInstance()->getStore()->getSiteLinkTable();
			$entityId = $siteLinkLookup->getEntityIdForSiteLink(
				new SiteLink( $site, $title->getFullText() )
			);

			if( $entityId ) {
				// Creating a Repo link with Item ID as anchor text
				$repoLinker = WikibaseClient::getDefaultInstance()->newRepoLinker();
				$idFormatter = WikibaseClient::getDefaultInstance()->getEntityIdFormatter();
				$idString = $idFormatter->format( $entityId );
				$itemLink = \Linker::makeExternalLink( $repoLinker->repoItemUrl( $entityId ), $idString, true, 'plainlink' );

				// Adding the Repo link to array &$pageInfo
				$pageInfo['header-basic'][] = array(
					$context->msg( 'wikibase-pageinfo-entity-id' ),
					$itemLink
				);
			} else {
				$pageInfo['header-basic'][] = array(
					$context->msg( 'wikibase-pageinfo-entity-id' ),
					$context->msg( 'wikibase-pageinfo-entity-id-none' )
				);
			}
		}
		return true;
	}

	/**
	 * After a page has been moved also update the item on the repo
	 * This only works with CentralAuth
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleMoveComplete
	 *
	 * @param Title $oldTitle
	 * @param Title $newTitle
	 * @param User $user
	 * @param integer $pageid database ID of the page that's been moved
	 * @param integer $redirid database ID of the created redirect
	 *
	 * @return bool
	 */
	public static function onTitleMoveComplete( $oldTitle, $newTitle, $user, $pageId, $redirectId ) {
		wfProfileIn( __METHOD__ );

		$updateRepo = UpdateRepoOnMove::newFromMove( $oldTitle, $newTitle, $user );

		if ( !$updateRepo || !$updateRepo->verifyRepoUser() ) {
			wfProfileOut( __METHOD__ );
			return true;
		}

		if ( $updateRepo->injectJob() ) {
			// To be able to find out about this in the SpecialMovepageAfterMove hook
			$newTitle->wikibasePushedMoveToRepo = true;
		}

		wfProfileOut( __METHOD__ );
		return true;
	}
}
