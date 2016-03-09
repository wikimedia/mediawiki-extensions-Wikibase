<?php

namespace Wikibase;

use Action;
use BaseTemplate;
use ChangesList;
use Diff\DiffOp\DiffOpAdd;
use IContextSource;
use Message;
use OutputPage;
use Parser;
use RecentChange;
use Skin;
use StubObject;
use Title;
use UnexpectedValueException;
use User;
use Wikibase\Client\DataAccess\Scribunto\Scribunto_LuaWikibaseEntityLibrary;
use Wikibase\Client\DataAccess\Scribunto\Scribunto_LuaWikibaseLibrary;
use Wikibase\Client\Hooks\BaseTemplateAfterPortletHandler;
use Wikibase\Client\Hooks\BeforePageDisplayHandler;
use Wikibase\Client\Hooks\DeletePageNoticeCreator;
use Wikibase\Client\Hooks\InfoActionHookHandler;
use Wikibase\Client\RecentChanges\ChangeLineFormatter;
use Wikibase\Client\RecentChanges\ExternalChangeFactory;
use Wikibase\Client\Specials\SpecialPagesWithBadges;
use Wikibase\Client\Specials\SpecialUnconnectedPages;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\ItemChange;
use Wikibase\Lib\AutoCommentFormatter;

/**
 * File defining the hook handlers for the Wikibase Client extension.
 *
 * @since 0.1
 *
 * @license GPL-2.0+
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
	 * @param string[] &$paths
	 *
	 * @return bool
	 */
	public static function registerUnitTests( array &$paths ) {
		$paths[] = __DIR__ . '/tests/phpunit/';

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
			$extraLibraries['mw.wikibase'] = Scribunto_LuaWikibaseLibrary::class;
			$extraLibraries['mw.wikibase.entity'] = Scribunto_LuaWikibaseEntityLibrary::class;
		}

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

		$type = $rc->getAttribute( 'rc_type' );

		if ( $type == RC_EXTERNAL ) {
			$wikibaseClient = WikibaseClient::getDefaultInstance();
			$changeFactory = new ExternalChangeFactory(
				$wikibaseClient->getSettings()->getSetting( 'repoSiteId' ),
				$wikibaseClient->getContentLanguage()
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

		return true;
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
	 *
	 * @return bool
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

		StubObject::unstub( $wgContLang );

		$formatter = new AutoCommentFormatter( $wgContLang, array( 'wikibase-entity' ) );
		$formattedComment = $formatter->formatAutoComment( $auto );

		if ( is_string( $formattedComment ) ) {
			$comment = $formatter->wrapAutoComment( $pre, $formattedComment, $post );
		}
	}

	/**
	 * Add Wikibase item link in toolbox
	 *
	 * @since 0.4
	 *
	 * @param BaseTemplate $baseTemplate
	 * @param array $toolbox
	 *
	 * @return bool
	 */
	public static function onBaseTemplateToolbox( BaseTemplate $baseTemplate, array &$toolbox ) {
		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$skin = $baseTemplate->getSkin();
		$idString = $skin->getOutput()->getProperty( 'wikibase_item' );
		$entityId = null;

		if ( $idString !== null ) {
			$entityIdParser = $wikibaseClient->getEntityIdParser();
			$entityId = $entityIdParser->parse( $idString );
		} elseif ( Action::getActionName( $skin ) !== 'view' && $skin->getTitle()->exists() ) {
			// Try to load the item ID from Database, but only do so on non-article views,
			// (where the article's OutputPage isn't available to us).
			$entityId = self::getEntityIdForTitle( $skin->getTitle() );
		}

		if ( $entityId !== null ) {
			$repoLinker = $wikibaseClient->newRepoLinker();
			$toolbox['wikibase'] = array(
				'text' => $baseTemplate->getMsg( 'wikibase-dataitem' )->text(),
				'href' => $repoLinker->getEntityUrl( $entityId ),
				'id' => 't-wikibase'
			);
		}

		return true;
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
		$namespaceChecker = WikibaseClient::getDefaultInstance()->getNamespaceChecker();
		$beforePageDisplayHandler = new BeforePageDisplayHandler( $namespaceChecker );

		$actionName = Action::getActionName( $skin->getContext() );
		$beforePageDisplayHandler->addModules( $out, $actionName );

		return true;
	}

	/**
	 * Initialise beta feature preferences
	 *
	 * @since 0.5
	 *
	 * @param User $user
	 * @param array $betaPreferences
	 *
	 * @return bool
	 */
	public static function onGetBetaFeaturePreferences( User $user, array &$betaPreferences ) {
		global $wgExtensionAssetsPath;

		preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
			. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

		$assetsPath = $wgExtensionAssetsPath . DIRECTORY_SEPARATOR . '..' . $remoteExtPath[0];

		$settings = WikibaseClient::getDefaultInstance()->getSettings();
		if ( !$settings->getSetting( 'otherProjectsLinksBeta' ) || $settings->getSetting( 'otherProjectsLinksByDefault' ) ) {
			return true;
		}

		$betaPreferences['wikibase-otherprojects'] = array(
			'label-message' => 'wikibase-otherprojects-beta-message',
			'desc-message' => 'wikibase-otherprojects-beta-description',
			'screenshot' => array(
				'ltr' => $assetsPath . '/resources/images/wb-otherprojects-beta-ltr.svg',
				'rtl' => $assetsPath . '/resources/images/wb-otherprojects-beta-rtl.svg'
			),
			'info-link' => 'https://www.mediawiki.org/wiki/Wikibase/Beta_Features/Other_projects_sidebar',
			'discussion-link' => 'https://www.mediawiki.org/wiki/Talk:Wikibase/Beta_Features/Other_projects_sidebar'
		);

		return true;
	}

	/**
	 * Adds a preference for showing or hiding Wikidata entries in recent changes
	 *
	 * @param User $user
	 * @param array[] &$prefs
	 *
	 * @return bool
	 */
	public static function onGetPreferences( User $user, array &$prefs ) {
		$settings = WikibaseClient::getDefaultInstance()->getSettings();

		if ( !$settings->getSetting( 'showExternalRecentChanges' ) ) {
			return true;
		}

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
		WikibaseClient::getDefaultInstance()->getParserFunctionRegistrant()->register( $parser );

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
	public static function onParserGetVariableValueSwitch( Parser &$parser, &$cache, &$magicWordId, &$ret ) {
		if ( $magicWordId === 'noexternallanglinks' ) {
			NoLangLinkHandler::handle( $parser, '*' );
		} elseif ( $magicWordId === 'wbreponame' ) {
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

		$namespaceChecker = $wikibaseClient->getNamespaceChecker();

		if ( !$namespaceChecker->isWikibaseEnabled( $context->getTitle()->getNamespace() ) ) {
			// shorten out
			return true;
		}

		$infoActionHookHandler = new InfoActionHookHandler(
			$namespaceChecker,
			$wikibaseClient->newRepoLinker(),
			$wikibaseClient->getStore()->getSiteLinkLookup(),
			$settings->getSetting( 'siteGlobalID' )
		);

		$pageInfo = $infoActionHookHandler->handle( $context, $pageInfo );

		return true;
	}

	/**
	 * Notify the user that we have automatically updated the repo or that they
	 * need to do that per hand.
	 *
	 * @param Title $title
	 * @param OutputPage $out
	 *
	 * @return bool
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
		$link = $handler->getEditLink( $skinTemplate, $name );

		if ( $link ) {
			$html .= $link;
		}
	}

	public static function onwgQueryPages( &$queryPages ) {
		$queryPages[] = array( SpecialUnconnectedPages::class, 'UnconnectedPages' );
		$queryPages[] = array( SpecialPagesWithBadges::class, 'PagesWithBadges' );
		return true;
	}

	public static function onExtensionLoad() {
		if ( class_exists( '\\EchoEvent' ) ) {
			$wgHooks['BeforeCreateEchoEvent'][] = '\Wikibase\ClientHooks::onBeforeCreateEchoEvent';
			$wgHooks['WikibaseHandleChange'][] = '\Wikibase\ClientHooks::onWikibaseHandleChange';
			$wgHooks['EchoGetBundleRules'][] = '\Wikibase\ClientHooks::onEchoGetBundleRules';
		}
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Extension:Echo/BeforeCreateEchoEvent
	 */
	public static function onBeforeCreateEchoEvent( &$notifications, &$notificationCategories, &$icons ) {
		$notificationCategories['wikibase-action'] = array(
			'priority' => 5, // really?
			'tooltip' => 'echo-pref-tooltip-page-connection',
		);

		$notifications['page-connection'] = array(
			'user-locators' => array(
				'EchoUserLocator::locateArticleCreator',
			),
			'category' => 'wikibase-action',
			'group' => 'neutral',
			'section' => 'alert',
			'presentation-model' => '\\Wikibase\\Client\\Notifications\\PageConnectionPresentationModel',
			'formatter-class' => '\\Wikibase\\Client\\Notifications\\PageConnectionFormatter',
			'bundle' => array( 'web' => true, 'email' => false ),
			'email-subject-message' => 'notification-page-connection-email-subject',
			'email-subject-params' => array( 'user', 'agent', 'page-count' ),
			'email-body-batch-message' => 'notification-page-connection-email-batch-body',
			'email-body-batch-params' => array( 'title', 'agent', 'item' ),
			'icon' => 'page-connection',
		);

		$path = 'Echo/modules/icons/Generic.png'; // need to find an icon that fits
		$icons += array(
			'page-connection' => array(
				'path' => $path
			)
		);

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Notifications/Developer_guide#Bundled_notifications
	 */
	public static function onEchoGetBundleRules( $event, &$bundleString ) {
		if ( $event->getType() == 'page-connection' ) {
			$bundleString = 'page-connection';
		}

		return true;
	}

	/**
	 * Helper function for self::onWikibaseHandleChange
	 *
	 * @param Title $title
	 *
	 * @return boolean
	 */
	private static function canNotifyForTitle( Title $title ) {
		return (bool)$title && $title->exists() && !$title->isRedirect()
			&& \MWNamespace::isContent( $title->getNamespace() );
	}

	/**
	 * @note This hook is triggered by the same extension
	 */
	public static function onWikibaseHandleChange( $change ) {
		if ( !( $change instanceof ItemChange ) ) {
			return;
		}

		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$settings = $wikibaseClient->getSettings();
		if ( $settings->getSetting( 'sendEchoNotification' ) !== true ) {
			return;
		}

		$siteId = $settings->getSetting( 'siteGlobalID' );
		$siteLinkDiff = $change->getSiteLinkDiff();

		if ( !array_key_exists( $siteId, $siteLinkDiff ) ) {
			return;
		}

		$siteLinkDiffOp = $siteLinkDiff[$siteId]['name'];

		if ( !( $siteLinkDiffOp instanceof DiffOpAdd ) ) {
			// FIXME: could also be DiffOpChange but that would also notify on page moves
			// checking comment would be hacky solution but sitelink can also be updated by hand
			// maybe compare old and new title?
			return;
		}

		$new = $siteLinkDiffOp->getNewValue();
		$title = Title::newFromText( $new );
		if ( self::canNotifyForTitle( $title ) ) {
			$metadata = $change->getMetadata();
			$entityId = $change->getEntityId();
			$repoLinker = $wikibaseClient->newRepoLinker();
			$agent = User::newFromName( $metadata['user_text'], false );
			\EchoEvent::create( array(
				'agent' => $agent,
				'extra' => array(
					'entityId' => $entityId,
					'url' => $repoLinker->getEntityUrl( $entityId ),
					// maybe also add diff link?
				),
				'title' => $title,
				'type' => 'page-connection'
			) );
		}

		return true;
	}
}