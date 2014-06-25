<?php

namespace Wikibase;

use Action;
use BaseTemplate;
use ChangesList;
use FormOptions;
use IContextSource;
use JobQueueGroup;
use Message;
use MovePageForm;
use MWException;
use OutputPage;
use Parser;
use ParserOutput;
use QuickTemplate;
use RecentChange;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Skin;
use SpecialRecentChanges;
use SpecialWatchlist;
use SplFileInfo;
use StripState;
use Title;
use UnexpectedValueException;
use User;
use Wikibase\Client\Hooks\BaseTemplateAfterPortletHandler;
use Wikibase\Client\Hooks\BeforePageDisplayHandler;
use Wikibase\Client\Hooks\InfoActionHookHandler;
use Wikibase\Client\Hooks\LanguageLinkBadgeDisplay;
use Wikibase\Client\Hooks\OtherProjectsSidebarGenerator;
use Wikibase\Client\Hooks\SpecialWatchlistQueryHandler;
use Wikibase\Client\MovePageNotice;
use Wikibase\Client\WikibaseClient;

/**
 * File defining the hook handlers for the Wikibase Client extension.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 *
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Jens Ohlig
 * @author Daniel Kinzler
 * @author Tobias Gritschacher
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
final class ClientHooks {

	/**
	 * @see NamespaceChecker::isWikibaseEnabled
	 *
	 * @param int $namespace
	 *
	 * @return bool
	 */
	protected static function isWikibaseEnabled( $namespace ) {
		return WikibaseClient::getDefaultInstance()->getNamespaceChecker()->isWikibaseEnabled( $namespace );
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

		$reportMessage( "Deleting data from the " . get_class( $store ) . " store..." );

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
		$reportMessage( "Rebuilding all data in the " . get_class( $store )
			. " store on the client..." );
		$store->rebuild();

		$changesTable = new ChangesTable();
		$changes = $changesTable->select(
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
	 * @param MovePageForm $movePage
	 * @param Title &$oldTitle
	 * @param Title &$newTitle
	 *
	 * @return bool
	 */
	public static function onSpecialMovepageAfterMove( MovePageForm $movePage, Title &$oldTitle,
		Title &$newTitle ) {
		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$siteLinkLookup = $wikibaseClient->getStore()->getSiteLinkTable();
		$repoLinker = $wikibaseClient->newRepoLinker();

		$movePageNotice = new MovePageNotice(
			$siteLinkLookup,
			$wikibaseClient->getSettings()->getSetting( 'siteGlobalID' ),
			$repoLinker
		);

		$html = $movePageNotice->getPageMoveNoticeHtml(
			$oldTitle,
			$newTitle
		);

		$out = $movePage->getOutput();
		$out->addModules( 'wikibase.client.page-move' );
		$out->addHTML( $html );

		return true;
	}

	/**
	 * External library for Scribunto
	 *
	 * @since 0.4
	 *
	 * @param string $engine
	 * @param array $extraLibraries
	 * @return bool
	 */
	public static function onScribuntoExternalLibraries( $engine, array &$extraLibraries ) {
		$allowDataTransclusion = WikibaseClient::getDefaultInstance()->getSettings()->getSetting( 'allowDataTransclusion' );
		if ( $engine == 'lua' && $allowDataTransclusion === true ) {
			$extraLibraries['mw.wikibase'] = 'Scribunto_LuaWikibaseLibrary';
			$extraLibraries['mw.wikibase.entity'] = 'Scribunto_LuaWikibaseEntityLibrary';
		}

		return true;
	}

	/**
	 * Hook for modifying the query for fetching recent changes
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ChangesListSpecialPageQuery
	 *
	 * @since 0.2
	 *
	 * @param &$conds[]
	 * @param &$tables[]
	 * @param &$join_conds[]
	 * @param FormOptions $opts
	 * @param &$query_options[]
	 * @param &$fields[]
	 *
	 * @return bool
	 */
	public static function onChangesListSpecialPageQuery( array &$conds, array &$tables,
		array &$join_conds, FormOptions $opts, array &$query_options, array &$fields
	) {
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
	 * @param ChangesList $changesList
	 * @param string $s
	 * @param RecentChange $rc
	 * @param string[] &$classes
	 *
	 * @return bool
	 */
	public static function onOldChangesListRecentChangesLine( ChangesList &$changesList, &$s,
		RecentChange $rc, &$classes = array() ) {

		wfProfileIn( __METHOD__ );

		$type = $rc->getAttribute( 'rc_type' );

		if ( $type == RC_EXTERNAL ) {
			$wikibaseClient = WikibaseClient::getDefaultInstance();
			$changeFactory = new ExternalChangeFactory(
				$wikibaseClient->getSettings()->getSetting( 'repoSiteId' )
			);

			try {
				$externalChange = $changeFactory->newFromRecentChange( $rc );
			} catch ( UnexpectedValueException $ex ) {
				// @fixme use rc_source column to better distinguish
				// Wikibase changes vs. non-wikibase, and unexpected
				// stuff in Wikibase changes.
				wfWarn( 'Invalid or not a Wikibase change.' );
				return false;
			}

			// fixme: inject formatter and flags into a changes list formatter
			$formatter = new ChangeLineFormatter(
				$changesList->getUser(),
				$changesList->getLanguage(),
				$wikibaseClient->newRepoLinker()
			);

			$flag = $changesList->recentChangesFlags( array( 'wikibase-edit' => true ), '' );
			$line = $formatter->format( $externalChange, $rc->getTitle(), $rc->counter, $flag );

			$classes[] = 'wikibase-edit';
			$s = $line;
		}

		// OutputPage will ignore multiple calls
		$changesList->getOutput()->addModuleStyles( 'wikibase.client.changeslist.css' );

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
	 * @param FormOptions|null $opts
	 *
	 * @return bool
	 */
	public static function onSpecialWatchlistQuery( array &$conds, array &$tables,
		array &$join_conds, array &$fields, $opts
	) {
		$db = wfGetDB( DB_SLAVE );
		$handler = new SpecialWatchlistQueryHandler( $GLOBALS['wgUser'], $db );

		$conds = $handler->addWikibaseConditions( $GLOBALS['wgRequest'], $conds, $opts );

		return true;
	}

	/**
	 * Hook runs after internal parsing
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserAfterParse
	 *
	 * @since 0.1
	 *
	 * @param Parser $parser
	 * @param string $text
	 * @param StripState $stripState
	 *
	 * @return bool
	 */
	public static function onParserAfterParse( Parser &$parser, &$text, StripState $stripState ) {
		// this hook tries to access repo SiteLinkTable
		// it interferes with any test that parses something, like a page or a message
		if ( defined( 'MW_PHPUNIT_TEST' ) ) {
			return true;
		}

		if ( !self::isWikibaseEnabled( $parser->getTitle()->getNamespace() ) ) {
			// shorten out
			return true;
		}

		wfProfileIn( __METHOD__ );

		// @todo split up the multiple responsibilities here and in lang link handler

		$parserOutput = $parser->getOutput();

		// only run this once, for the article content and not interface stuff
		//FIXME: this also runs for messages in EditPage::showEditTools! Ugh!
		if ( $parser->getOptions()->getInterfaceMessage() ) {
			wfProfileOut( __METHOD__ );
			return true;
		}

		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$settings = $wikibaseClient->getSettings();

		$langLinkHandler = new LangLinkHandler(
			$settings->getSetting( 'siteGlobalID' ),
			$wikibaseClient->getNamespaceChecker(),
			$wikibaseClient->getStore()->getSiteLinkTable(),
			$wikibaseClient->getSiteStore(),
			$wikibaseClient->getLangLinkSiteGroup()
		);

		$useRepoLinks = $langLinkHandler->useRepoLinks( $parser->getTitle(), $parser->getOutput() );

		try {
			if ( $useRepoLinks ) {
				// add links
				$langLinkHandler->addLinksFromRepository( $parser->getTitle(), $parser->getOutput() );
			}

			$langLinkHandler->updateItemIdProperty( $parser->getTitle(), $parser->getOutput() );
		} catch ( \Exception $e ) {
			wfWarn( 'Failed to add repo links: ' . $e->getMessage() );
		}

		if ( $useRepoLinks || $settings->getSetting( 'alwaysSort' ) ) {
			// sort links
			$interwikiSorter = new InterwikiSorter(
				$settings->getSetting( 'sort' ),
				$settings->getSetting( 'interwikiSortOrders' ),
				$settings->getSetting( 'sortPrepend' )
			);
			$interwikiLinks = $parserOutput->getLanguageLinks();
			$sortedLinks = $interwikiSorter->sortLinks( $interwikiLinks );
			$parserOutput->setLanguageLinks( $sortedLinks );
		}

		wfProfileOut( __METHOD__ );
		return true;
	}

	/**
	 * Add badges to the language links.
	 *
	 * @since 0.5
	 *
	 * @param array &$languageLink
	 * @param Title $languageLinkTitle
	 * @param Title $title
	 *
	 * @return bool
	 */
	public static function onSkinTemplateGetLanguageLink( &$languageLink, Title $languageLinkTitle, Title $title ) {
		wfProfileIn( __METHOD__ );

		global $wgLang;

		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$settings = $wikibaseClient->getSettings();

		$clientSiteLinkLookup = $wikibaseClient->getClientSiteLinkLookup();
		$entityLookup = $wikibaseClient->getStore()->getEntityLookup();
		$sites = $wikibaseClient->getSiteStore()->getSites();
		$badgeClassNames = $settings->getSetting( 'badgeClassNames' );

		if ( !is_array( $badgeClassNames ) ) {
			$badgeClassNames = array();
		}

		$languageLinkBadgeDisplay = new LanguageLinkBadgeDisplay(
			$clientSiteLinkLookup,
			$entityLookup,
			$sites,
			$badgeClassNames,
			$wgLang
		);

		$languageLinkBadgeDisplay->assignBadges( $title, $languageLinkTitle, $languageLink );

		wfProfileOut( __METHOD__ );
		return true;
	}

	/**
	 * Add Wikibase item link in toolbox
	 *
	 * @since 0.4
	 *
	 * @param QuickTemplate &$sk
	 * @param array &$toolbox
	 *
	 * @return bool
	 */
	public static function onBaseTemplateToolbox( QuickTemplate &$sk, &$toolbox ) {
		$prefixedId = $sk->getSkin()->getOutput()->getProperty( 'wikibase_item' );

		if ( $prefixedId !== null ) {
			$entityIdParser = WikibaseClient::getDefaultInstance()->getEntityIdParser();
			$entityId = $entityIdParser->parse( $prefixedId );

			$repoLinker = WikibaseClient::getDefaultInstance()->newRepoLinker();
			$itemLink = $repoLinker->getEntityUrl( $entityId );

			$toolbox['wikibase'] = array(
				'text' => $sk->getMsg( 'wikibase-dataitem' )->text(),
				'href' => $itemLink,
				'id' => 't-wikibase'
			);
		}

		return true;
	}

	/**
	 * Add the connected item prefixed id as a JS config variable, for gadgets etc.
	 *
	 * @param OutputPage &$out
	 * @param Skin &$skin
	 *
	 * @since 0.4
	 *
	 * @return bool
	 */
	 public static function onBeforePageDisplayAddJsConfig( OutputPage &$out, Skin &$skin ) {
		$prefixedId = $out->getProperty( 'wikibase_item' );

		if ( $prefixedId !== null ) {
			$out->addJsConfigVars( 'wgWikibaseItemId', $prefixedId );
		}

		return true;
	}

	/**
	 * Adds css for the edit links sidebar link or JS to create a new item
	 * or to link with an existing one.
	 *
	 * @param OutputPage &$out
	 * @param Skin &$skin
	 *
	 * @since 0.1
	 *
	 * @return bool
	 */
	public static function onBeforePageDisplay( OutputPage &$out, Skin &$skin ) {
		wfProfileIn( __METHOD__ );

		$namespaceChecker = WikibaseClient::getDefaultInstance()->getNamespaceChecker();
		$beforePageDisplayHandler = new BeforePageDisplayHandler( $namespaceChecker );

		$actionName = Action::getActionName( $skin->getContext() );
		$beforePageDisplayHandler->addModules( $out, $skin, $actionName );

		wfProfileOut( __METHOD__ );

		return true;
	}

	/**
	 * Add output page property if repo links are suppressed, and property for item id
	 *
	 * @since 0.4
	 *
	 * @param OutputPage &$out
	 * @param ParserOutput $pout
	 *
	 * @return bool
	 */
	public static function onOutputPageParserOutput( OutputPage &$out, ParserOutput $pout ) {
		if ( !self::isWikibaseEnabled( $out->getTitle()->getNamespace() ) ) {
			// shorten out
			return true;
		}

		$langLinkHandler = WikibaseClient::getDefaultInstance()->getLangLinkHandler();

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
	 * @param Skin $skin
	 * @param QuickTemplate $template
	 *
	 * @return bool
	 */
	public static function onSkinTemplateOutputPageBeforeExec( Skin &$skin, QuickTemplate &$template ) {
		$title = $skin->getContext()->getTitle();
		$wikibaseClient = WikibaseClient::getDefaultInstance();

		if ( !self::isWikibaseEnabled( $title->getNamespace() ) ) {
			// shorten out
			return true;
		}

		wfProfileIn( __METHOD__ );

		$repoLinker = $wikibaseClient->newRepoLinker();
		$entityIdParser = $wikibaseClient->getEntityIdParser();

		$siteGroup = $wikibaseClient->getSiteGroup();

		$langLinkGenerator = new RepoItemLinkGenerator(
			WikibaseClient::getDefaultInstance()->getNamespaceChecker(),
			$repoLinker,
			$entityIdParser,
			$siteGroup
		);

		$action = Action::getActionName( $skin->getContext() );

		$isAnon = ! $skin->getContext()->getUser()->isLoggedIn();
		$noExternalLangLinks = $skin->getOutput()->getProperty( 'noexternallanglinks' );
		$prefixedId = $skin->getOutput()->getProperty( 'wikibase_item' );

		$editLink = $langLinkGenerator->getLink( $title, $action, $isAnon, $noExternalLangLinks, $prefixedId );

		// there will be no link in some situations, like add links widget disabled
		if ( $editLink ) {
			$template->set( 'wbeditlanglinks', $editLink );
		}

		// needed to have "Other languages" section display, so we can add "add links"
		// by default, the css then hides it if the widget is not enabled for a page or user
		if ( $template->get( 'language_urls' ) === false && $title->exists() ) {
			$template->set( 'language_urls', array() );
		}

		wfProfileOut( __METHOD__ );
		return true;
	}

	/**
	 * Displays a sidebar section for other project links.
	 *
	 * @since 0.5
	 *
	 * @param Skin $skin
	 * @param array $bar
	 *
	 * @return bool
	 */
	public static function onSkinBuildSidebar( Skin $skin, &$bar ) {
		$settings = WikibaseClient::getDefaultInstance()->getSettings();

		$siteIdsToOutput = $settings->getSetting( 'otherProjectsLinks' );
		if ( count( $siteIdsToOutput ) === 0 ) {
			return true;
		}

		$generator = new OtherProjectsSidebarGenerator(
			$settings->getSetting( 'siteGlobalID' ),
			WikibaseClient::getDefaultInstance()->getStore()->getSiteLinkTable(),
			WikibaseClient::getDefaultInstance()->getSiteStore(),
			$siteIdsToOutput
		);

		$otherProjectsSidebar = $generator->buildProjectLinkSidebar( $skin->getContext()->getTitle() );
		if ( count( $otherProjectsSidebar ) !== 0 ) {
			$bar['wikibase-otherprojects'] = $otherProjectsSidebar;
		}

		return true;
	}

	/**
	 * Adds a toggle for showing/hiding Wikidata entries in recent changes
	 *
	 * @param SpecialRecentChanges $special
	 * @param array &$filters
	 *
	 * @return bool
	 */
	public static function onChangesListSpecialPageFilters( SpecialRecentChanges $special, array &$filters ) {
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
	 * @param User $user
	 * @param &$prefs[]
	 *
	 * @return bool
	 */
	public static function onGetPreferences( User $user, array &$prefs ) {
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
	 * @param $parser Parser
	 *
	 * @return bool
	 */
	public static function onParserFirstCallInit( Parser &$parser ) {
		$parser->setFunctionHook( 'noexternallanglinks', '\Wikibase\NoLangLinkHandler::handle', SFH_NO_HASH );
		$allowDataTransclusion = WikibaseClient::getDefaultInstance()->getSettings()->getSetting( 'allowDataTransclusion' );

		if ( $allowDataTransclusion === true ) {
			$parser->setFunctionHook( 'property', array( '\Wikibase\PropertyParserFunction', 'render' ) );
		}

		return true;
	}

	/**
	 * Register the magic word.
	 */
	public static function onMagicWordwgVariableIDs( &$aCustomVariableIds ) {
		$aCustomVariableIds[] = 'noexternallanglinks';
		$aCustomVariableIds[] = 'wbreponame';

		return true;
	}

	/**
	 * Apply the magic word.
	 */
	public static function onParserGetVariableValueSwitch( &$parser, &$cache, &$magicWordId, &$ret ) {
		if ( $magicWordId == 'noexternallanglinks' ) {
			NoLangLinkHandler::handle( $parser, '*' );
		} elseif ( $magicWordId == 'wbreponame' ) {
			// @todo factor out, with tests
			$wikibaseClient = WikibaseClient::getDefaultInstance();
			$settings = $wikibaseClient->getSettings();
			$repoSiteName = $settings->getSetting( 'repoSiteName' );

			$message = new Message( $repoSiteName );

			if ( $message->exists() ) {
				$lang = $parser->getTargetLanguage();
				$ret = $message->inLanguage( $lang )->parse();
			} else {
				$ret = $repoSiteName;
			}
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

		if ( $special->getContext()->getRequest()->getBool( 'enhanced',
			$user->getOption( 'usenewrc' ) ) === false ) {
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
	public static function onInfoAction( IContextSource $context, array &$pageInfo ) {
		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$settings = $wikibaseClient->getSettings();

		$namespaceChecker = WikibaseClient::getDefaultInstance()->getNamespaceChecker();

		if ( !$namespaceChecker->isWikibaseEnabled( $context->getTitle()->getNamespace() ) ) {
			// shorten out
			return true;
		}

		$infoActionHookHandler = new InfoActionHookHandler(
			$namespaceChecker,
			$wikibaseClient->newRepoLinker(),
			$wikibaseClient->getStore()->getSiteLinkTable(),
			$settings->getSetting( 'siteGlobalID' )
		);

		$pageInfo = $infoActionHookHandler->handle( $context, $pageInfo );

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
	 * @param integer $pageId database ID of the page that's been moved
	 * @param integer $redirectId database ID of the created redirect
	 *
	 * @return bool
	 */
	public static function onTitleMoveComplete( Title $oldTitle, Title $newTitle, User $user,
		$pageId, $redirectId ) {

		if ( !self::isWikibaseEnabled( $oldTitle->getNamespace() )
			&& !self::isWikibaseEnabled( $newTitle->getNamespace() ) ) {
			// shorten out
			return true;
		}

		wfProfileIn( __METHOD__ );

		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$settings = $wikibaseClient->getSettings();

		if ( $settings->getSetting( 'propagateChangesToRepo' ) !== true ) {
			wfProfileOut( __METHOD__ );
			return true;
		}

		$repoDB = $settings->getSetting( 'repoDatabase' );
		$siteLinkLookup = $wikibaseClient->getStore()->getSiteLinkTable();
		$jobQueueGroup = JobQueueGroup::singleton( $repoDB );

		if ( !$jobQueueGroup ) {
			wfLogWarning( "Failed to acquire a JobQueueGroup for $repoDB" );
			wfProfileOut( __METHOD__ );
			return true;
		}

		$updateRepo = new UpdateRepoOnMove(
			$repoDB,
			$siteLinkLookup,
			$user,
			$settings->getSetting( 'siteGlobalID' ),
			$oldTitle,
			$newTitle
		);

		if ( !$updateRepo || !$updateRepo->getEntityId() || !$updateRepo->userIsValidOnRepo() ) {
			wfProfileOut( __METHOD__ );
			return true;
		}

		try {
			$updateRepo->injectJob( $jobQueueGroup );

			// To be able to find out about this in the SpecialMovepageAfterMove hook
			$newTitle->wikibasePushedMoveToRepo = true;
		} catch( MWException $e ) {
			// This is not a reason to let an exception bubble up, we just
			// show a message to the user that the Wikibase item needs to be
			// manually updated.
			wfLogWarning( $e->getMessage() );
		} catch( RuntimeException $e ) {
			// B/C for MediaWiki 1.23
			wfLogWarning( $e->getMessage() );
		}

		wfProfileOut( __METHOD__ );
		return true;
	}

	/**
	 * @param BaseTemplate $skinTemplate
	 * @param string $name
	 * @param string &$html
	 *
	 * @return boolean
	 */
	public static function onBaseTemplateAfterPortlet( BaseTemplate $skinTemplate, $name, &$html ) {
		$handler = new BaseTemplateAfterPortletHandler();
		$link = $handler->makeEditLink( $skinTemplate, $name );

		if ( $link ) {
			$html .= $link;
		}
	}
}
