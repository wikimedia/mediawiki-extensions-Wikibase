<?php

namespace Wikibase;

use Action;
use BaseTemplate;
use ContentHandler;
use EditPage;
use ExtensionRegistry;
use OutputPage;
use Parser;
use ParserOutput;
use RecentChange;
use SearchEngine;
use SearchIndexField;
use Skin;
use Title;
use User;
use Wikibase\Client\DataAccess\Scribunto\Scribunto_LuaWikibaseEntityLibrary;
use Wikibase\Client\DataAccess\Scribunto\Scribunto_LuaWikibaseLibrary;
use Wikibase\Client\Hooks\BaseTemplateAfterPortletHandler;
use Wikibase\Client\Hooks\BeforePageDisplayHandler;
use Wikibase\Client\Hooks\ChangesListSpecialPageHookHandlers;
use Wikibase\Client\Hooks\DeletePageNoticeCreator;
use Wikibase\Client\Hooks\EchoNotificationsHandlers;
use Wikibase\Client\Hooks\EditActionHookHandler;
use Wikibase\Client\Hooks\SkinAfterBottomScriptsHandler;
use Wikibase\Client\MoreLikeWikibase;
use Wikibase\Client\RecentChanges\RecentChangeFactory;
use Wikibase\Client\Specials\SpecialUnconnectedPages;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lib\Formatters\AutoCommentFormatter;
use WikiPage;

/**
 * File defining the hook handlers for the Wikibase Client extension.
 *
 * @license GPL-2.0-or-later
 */
final class ClientHooks {

	const PAGE_SCHEMA_SPLIT_TEST_TREATMENT = 'treatment';

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
	 * @param string[] &$paths
	 */
	public static function registerUnitTests( array &$paths ) {
		$paths[] = __DIR__ . '/tests/phpunit/';
	}

	/**
	 * External library for Scribunto
	 *
	 * @param string $engine
	 * @param string[] &$extraLibraries
	 */
	public static function onScribuntoExternalLibraries( $engine, array &$extraLibraries ) {
		$allowDataTransclusion = WikibaseClient::getDefaultInstance()->getSettings()->getSetting( 'allowDataTransclusion' );
		if ( $engine == 'lua' && $allowDataTransclusion === true ) {
			$extraLibraries['mw.wikibase'] = Scribunto_LuaWikibaseLibrary::class;
			$extraLibraries['mw.wikibase.entity'] = Scribunto_LuaWikibaseEntityLibrary::class;
		}
	}

	/**
	 * Handler for the FormatAutocomments hook, implementing localized formatting
	 * for machine readable autocomments generated by SummaryFormatter.
	 *
	 * @param string &$comment reference to the autocomment text
	 * @param bool $pre true if there is content before the autocomment
	 * @param string $auto the autocomment unformatted
	 * @param bool $post true if there is content after the autocomment
	 * @param Title|null $title use for further information
	 * @param bool $local shall links be generated locally or globally
	 * @param string|null $wikiId The ID of the wiki the comment applies to, if not the local wiki.
	 */
	public static function onFormat( &$comment, $pre, $auto, $post, $title, $local, $wikiId = null ) {
		global $wgContLang;

		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$repoId = $wikibaseClient->getSettings()->getSetting( 'repoSiteId' );

		// Only do special formatting for comments from a wikibase repo.
		// XXX: what to do if the local wiki is the repo? For entity pages, RepoHooks has a handler.
		// But what to do for other pages? Note that if the local wiki is the repo, $repoId will be
		// false, and $wikiId will be null.
		if ( $wikiId !== $repoId ) {
			return;
		}

		$formatter = new AutoCommentFormatter( $wgContLang, [ 'wikibase-entity' ] );
		$formattedComment = $formatter->formatAutoComment( $auto );

		if ( is_string( $formattedComment ) ) {
			$comment = $formatter->wrapAutoComment( $pre, $formattedComment, $post );
		}
	}

	/**
	 * Add Wikibase item link in toolbox
	 *
	 * @param BaseTemplate $baseTemplate
	 * @param array[] &$toolbox
	 */
	public static function onBaseTemplateToolbox( BaseTemplate $baseTemplate, array &$toolbox ) {
		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$skin = $baseTemplate->getSkin();
		$title = $skin->getTitle();
		$idString = $skin->getOutput()->getProperty( 'wikibase_item' );
		$entityId = null;

		if ( $idString !== null ) {
			$entityIdParser = $wikibaseClient->getEntityIdParser();
			$entityId = $entityIdParser->parse( $idString );
		} elseif ( $title && Action::getActionName( $skin ) !== 'view' && $title->exists() ) {
			// Try to load the item ID from Database, but only do so on non-article views,
			// (where the article's OutputPage isn't available to us).
			$entityId = self::getEntityIdForTitle( $title );
		}

		if ( $entityId !== null ) {
			$repoLinker = $wikibaseClient->newRepoLinker();
			$toolbox['wikibase'] = [
				'text' => $baseTemplate->getMsg( 'wikibase-dataitem' )->text(),
				'href' => $repoLinker->getEntityUrl( $entityId ),
				'id' => 't-wikibase'
			];
		}
	}

	/**
	 * @param Title|null $title
	 *
	 * @return EntityId|null
	 */
	private static function getEntityIdForTitle( Title $title = null ) {
		if ( $title === null || !self::isWikibaseEnabled( $title->getNamespace() ) ) {
			return null;
		}

		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$entityIdLookup = $wikibaseClient->getStore()->getEntityIdLookup();
		return $entityIdLookup->getEntityIdForTitle( $title );
	}

	/**
	 * Add the connected item prefixed id as a JS config variable, for gadgets etc.
	 *
	 * @param OutputPage $out
	 * @param Skin $skin
	 */
	public static function onBeforePageDisplayAddJsConfig( OutputPage $out, Skin $skin ) {
		$prefixedId = $out->getProperty( 'wikibase_item' );

		if ( $prefixedId !== null ) {
			$out->addJsConfigVars( 'wgWikibaseItemId', $prefixedId );
		}
	}

	/**
	 * Adds css for the edit links sidebar link or JS to create a new item
	 * or to link with an existing one.
	 *
	 * @param OutputPage $out
	 * @param Skin $skin
	 */
	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
		$clientInstance = WikibaseClient::getDefaultInstance();
		$beforePageDisplayHandler = new BeforePageDisplayHandler(
			$clientInstance->getNamespaceChecker(),
			$clientInstance->getSettings()->getSetting( 'dataBridgeEnabled' )
		);

		$actionName = Action::getActionName( $skin->getContext() );
		$beforePageDisplayHandler->addModules( $out, $actionName );
	}

	/**
	 * Adds a preference for showing or hiding Wikidata entries in recent changes
	 *
	 * @param User $user
	 * @param array[] &$prefs
	 */
	public static function onGetPreferences( User $user, array &$prefs ) {
		$settings = WikibaseClient::getDefaultInstance()->getSettings();

		if ( !$settings->getSetting( 'showExternalRecentChanges' ) ) {
			return;
		}

		$prefs['rcshowwikidata'] = [
			'type' => 'toggle',
			'label-message' => 'wikibase-rc-show-wikidata-pref',
			'section' => 'rc/advancedrc',
		];

		$prefs['wlshowwikibase'] = [
			'type' => 'toggle',
			'label-message' => 'wikibase-watchlist-show-changes-pref',
			'section' => 'watchlist/advancedwatchlist',
		];
	}

	/**
	 * Register the parser functions.
	 *
	 * @param Parser $parser
	 */
	public static function onParserFirstCallInit( Parser $parser ) {
		WikibaseClient::getDefaultInstance()->getParserFunctionRegistrant()->register( $parser );
	}

	/**
	 * Adds the Entity usage data in ActionEdit
	 *
	 * @param EditPage $editor
	 * @param OutputPage $output
	 * @param int $tabindex
	 */
	public static function onEditAction( EditPage $editor, OutputPage $output, &$tabindex ) {
		if ( $editor->section ) {
			// Shorten out, like template transclusion in core
			return;
		}

		$editActionHookHandler = EditActionHookHandler::newFromGlobalState(
			$editor->getContext()
		);
		$editActionHookHandler->handle( $editor );

		$output->addModules( 'wikibase.client.action.edit.collapsibleFooter' );
	}

	/**
	 * Notify the user that we have automatically updated the repo or that they
	 * need to do that per hand.
	 *
	 * @param Title $title
	 * @param OutputPage $out
	 */
	public static function onArticleDeleteAfterSuccess( Title $title, OutputPage $out ) {
		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$siteLinkLookup = $wikibaseClient->getStore()->getSiteLinkLookup();
		$repoLinker = $wikibaseClient->newRepoLinker();

		$deletePageNotice = new DeletePageNoticeCreator(
			$siteLinkLookup,
			$wikibaseClient->getSettings()->getSetting( 'siteGlobalID' ),
			$repoLinker
		);

		$html = $deletePageNotice->getPageDeleteNoticeHtml( $title );

		$out->addHTML( $html );
	}

	/**
	 * @param BaseTemplate $skinTemplate
	 * @param string $name
	 * @param string &$html
	 */
	public static function onBaseTemplateAfterPortlet( BaseTemplate $skinTemplate, $name, &$html ) {
		$handler = new BaseTemplateAfterPortletHandler();
		$link = $handler->getEditLink( $skinTemplate, $name );

		if ( $link ) {
			$html .= $link;
		}
	}

	/**
	 * @param array[] &$queryPages
	 */
	public static function onwgQueryPages( &$queryPages ) {
		$queryPages[] = [ SpecialUnconnectedPages::class, 'UnconnectedPages' ];
		// SpecialPagesWithBadges and SpecialEntityUsage also extend QueryPage,
		// but are not useful in the list of query pages,
		// since they require a parameter (badge, entity id) to operate
	}

	/**
	 * @param User $editor
	 * @param Title $title
	 * @param RecentChange $recentChange
	 *
	 * @return bool
	 */
	public static function onAbortEmailNotification( User $editor, Title $title, RecentChange $recentChange ) {
		if ( $recentChange->getAttribute( 'rc_source' ) === RecentChangeFactory::SRC_WIKIBASE ) {
			return false;
		}

		return true;
	}

	/**
	 * Do special hook registrations.  These are affected by ordering issues and/or
	 * conditional on another extension being registered.
	 *
	 * @see https://www.mediawiki.org/wiki/Special:MyLanguage/Manual:$wgExtensionFunctions
	 */
	public static function onExtensionLoad() {
		global $wgHooks;

		// These hooks should only be run if we use the Echo extension
		if ( ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ) {
			$wgHooks['LocalUserCreated'][] = EchoNotificationsHandlers::class . '::onLocalUserCreated';
			$wgHooks['WikibaseHandleChange'][] = EchoNotificationsHandlers::class . '::onWikibaseHandleChange';
		}

		// This is in onExtensionLoad to ensure we register our
		// ChangesListSpecialPageStructuredFilters after ORES's.
		//
		// However, ORES is not required.
		//
		// recent changes / watchlist hooks
		$wgHooks['ChangesListSpecialPageStructuredFilters'][] =
			ChangesListSpecialPageHookHandlers::class . '::onChangesListSpecialPageStructuredFilters';
	}

	/**
	 * Register wikibase_item field.
	 * @param array $fields
	 * @param SearchEngine $engine
	 */
	public static function onSearchIndexFields( array &$fields, SearchEngine $engine ) {
		$fields['wikibase_item'] = $engine->makeSearchFieldMapping( 'wikibase_item',
				SearchIndexField::INDEX_TYPE_KEYWORD );
	}

	/**
	 * Put wikibase_item into the data.
	 * @param array $fields
	 * @param ContentHandler $handler
	 * @param WikiPage $page
	 * @param ParserOutput $output
	 * @param SearchEngine $engine
	 */
	public static function onSearchDataForIndex(
		array &$fields,
		ContentHandler $handler,
		WikiPage $page,
		ParserOutput $output,
		SearchEngine $engine
	) {
		$wikibaseItem = $output->getProperty( 'wikibase_item' );
		if ( $wikibaseItem ) {
			$fields['wikibase_item'] = $wikibaseItem;
		}
	}

	/**
	 * Add morelikewithwikibase keyword.
	 * @param $config
	 * @param array $extraFeatures
	 */
	public static function onCirrusSearchAddQueryFeatures(
		$config,
		array &$extraFeatures
	) {
		$extraFeatures[] = new MoreLikeWikibase( $config );
	}

	/**
	 * Injects a Wikidata inline JSON-LD script schema for search engine optimization.
	 *
	 * @param Skin $skin
	 * @param string &$html
	 *
	 * @return bool Always true.
	 */
	public static function onSkinAfterBottomScripts( Skin $skin, &$html ) {
		$client = WikibaseClient::getDefaultInstance();
		$enabledNamespaces = $client->getSettings()->getSetting( 'pageSchemaNamespaces' );

		$out = $skin->getOutput();
		$entityId = self::parseEntityId( $client, $out->getProperty( 'wikibase_item' ) );
		$title = $out->getTitle();
		if (
			!$entityId ||
			!$title ||
			!in_array( $title->getNamespace(), $enabledNamespaces ) ||
			!$title->exists()
		) {
			return true;
		}

		$handler = new SkinAfterBottomScriptsHandler( $client, $client->newRepoLinker() );
		$revisionTimestamp = $out->getRevisionTimestamp();
		$html .= $handler->createSchemaElement(
			$title,
			$revisionTimestamp,
			$entityId
		);

		return true;
	}

	/**
	 * @param WikibaseClient $client
	 * @param string|null $prefixedId
	 *
	 * @return EntityId|null
	 */
	private static function parseEntityId( WikibaseClient $client, $prefixedId = null ) {
		if ( !$prefixedId ) {
			return null;
		}

		try {
			return $client->getEntityIdParser()->parse( $prefixedId );
		} catch ( EntityIdParsingException $ex ) {
			return null;
		}
	}

}