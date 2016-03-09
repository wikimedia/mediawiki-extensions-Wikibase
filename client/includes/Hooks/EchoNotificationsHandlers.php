<?php

namespace Wikibase\Client\Hooks;

use Diff\DiffOp\DiffOp;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use EchoAttributeManager;
use EchoEvent;
use MWNamespace;
use Title;
use User;
use Wikibase\Change;
use Wikibase\Client\Notifications\PageConnectionPresentationModel;
use Wikibase\Client\RepoLinker;
use Wikibase\Client\WikibaseClient;
use Wikibase\ItemChange;
use WikiPage;

/**
 * Handlers for client Echo notifications
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Matěj Suchánek
 */
class EchoNotificationsHandlers {

	/**
	 * Type of notification
	 */
	const NOTIFICATION_TYPE = 'page-connection';

	/**
	 * @var RepoLinker
	 */
	private $repoLinker;

	/**
	 * @var string
	 */
	private $siteId;

	/**
	 * @var bool
	 */
	private $sendEchoNotification;

	/**
	 * @var array|false
	 */
	private $repoIcon;

	/**
	 * @var string
	 */
	private $repoSiteName;

	/**
	 * @param RepoLinker $repoLinker
	 * @param string $siteId
	 * @param bool $sendEchoNotification
	 * @param array|false $repoIcon
	 * @param string $repoSiteName
	 */
	public function __construct(
		RepoLinker $repoLinker,
		$siteId,
		$sendEchoNotification,
		$repoIcon,
		$repoSiteName
	) {
		$this->repoLinker = $repoLinker;
		$this->siteId = $siteId;
		$this->sendEchoNotification = $sendEchoNotification;
		$this->repoIcon = $repoIcon;
		$this->repoSiteName = $repoSiteName;
	}

	/**
	 * @return self
	 */
	public static function newFromGlobalState() {
		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$settings = $wikibaseClient->getSettings();

		return new self(
			$wikibaseClient->newRepoLinker(),
			$settings->getSetting( 'siteGlobalID' ),
			$settings->getSetting( 'sendEchoNotification' ),
			$settings->getSetting( 'repoIcon' ),
			$settings->getSetting( 'repoSiteName' )
		);
	}

	/**
	 * Handler for BeforeCreateEchoEvent hook
	 * @see https://www.mediawiki.org/wiki/Extension:Echo/BeforeCreateEchoEvent
	 * @see doBeforeCreateEchoEvent
	 */
	public static function onBeforeCreateEchoEvent(
		array &$notifications,
		array &$notificationCategories,
		array &$icons
	) {
		$self = self::newFromGlobalState();
		$self->doBeforeCreateEchoEvent( $notifications, $notificationCategories, $icons );
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Notifications/Developer_guide
	 *
	 * @param array[] &$notifications
	 * @param array[] &$notificationCategories
	 * @param array[] &$icons
	 */
	public function doBeforeCreateEchoEvent(
		array &$notifications,
		array &$notificationCategories,
		array &$icons
	) {
		$notificationCategories['wikibase-action'] = [
			'priority' => 5,
			'tooltip' => 'echo-pref-tooltip-wikibase-action',
		];

		$notifications[self::NOTIFICATION_TYPE] = [
			EchoAttributeManager::ATTR_LOCATORS => [
				'EchoUserLocator::locateArticleCreator',
			],
			'category' => 'wikibase-action',
			'group' => 'neutral',
			'section' => 'message',
			'presentation-model' => PageConnectionPresentationModel::class,
			'bundle' => [ 'web' => true, 'email' => false ],
		];

		if ( !empty( $this->repoIcon ) ) {
			$icons[self::NOTIFICATION_TYPE] = $this->repoIcon;
		} else {
			$icons[self::NOTIFICATION_TYPE] = $icons['placeholder'];
		}
	}

	/**
	 * Handler for EchoGetBundleRules hook
	 * @see https://www.mediawiki.org/wiki/Notifications/Developer_guide#Bundled_notifications
	 *
	 * @param EchoEvent $event
	 * @param string &$bundleString
	 */
	public static function onEchoGetBundleRules( EchoEvent $event, &$bundleString ) {
		if ( $event->getType() === self::NOTIFICATION_TYPE ) {
			$bundleString = self::NOTIFICATION_TYPE;
		}
	}

	/**
	 * Handler for UserGetDefaultOptions hook
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UserGetDefaultOptions
	 *
	 * @param bool[] &$defaultOptions Array of preference keys and their default values.
	 */
	public static function onUserGetDefaultOptions( array &$defaultOptions ) {
		$defaultOptions['echo-subscriptions-web-' . self::NOTIFICATION_TYPE] = true;
	}

	/**
	 * Handler for WikibaseHandleChange hook
	 * @see doWikibaseHandleChange
	 *
	 * @param Change $change
	 */
	public static function onWikibaseHandleChange( Change $change ) {
		$self = self::newFromGlobalState();
		$self->doWikibaseHandleChange( $change );
	}

	/**
	 * @param Change $change
	 *
	 * @return bool
	 */
	public function doWikibaseHandleChange( Change $change ) {
		if ( $this->sendEchoNotification !== true ) {
			return false;
		}

		if ( !( $change instanceof ItemChange ) ) {
			return false;
		}

		$siteLinkDiff = $change->getSiteLinkDiff();
		if ( $siteLinkDiff->isEmpty() ) {
			return false;
		}

		$siteId = $this->siteId;
		if ( !isset( $siteLinkDiff[$siteId] ) || !isset( $siteLinkDiff[$siteId]['name'] ) ) {
			return false;
		}

		$siteLinkDiffOp = $siteLinkDiff[$siteId]['name'];

		$title = $this->getTitleForNotification( $siteLinkDiffOp );
		if ( $title !== false ) {
			$metadata = $change->getMetadata();
			$entityId = $change->getEntityId();
			$agent = User::newFromName( $metadata['user_text'], false );
			EchoEvent::create( [
				'agent' => $agent,
				'extra' => [
					// maybe also a diff link?
					'url' => $this->repoLinker->getEntityUrl( $entityId ),
					'repoSiteName' => $this->repoSiteName,
				],
				'title' => $title,
				'type' => self::NOTIFICATION_TYPE
			] );

			return true;
		}

		return false;
	}

	/**
	 * Determines whether the change was a real sitelink addition
	 * and returns either title, or false
	 *
	 * @param DiffOp $siteLinkDiffOp
	 *
	 * @return Title|false
	 */
	private function getTitleForNotification( DiffOp $siteLinkDiffOp ) {
		if ( $siteLinkDiffOp instanceof DiffOpAdd ) {
			$new = $siteLinkDiffOp->getNewValue();
			$newTitle = Title::newFromText( $new );
			return $this->canNotifyForTitle( $newTitle ) ? $newTitle : false;
		}

		// if it's a sitelink change, make sure it wasn't triggered by a page move
		if ( $siteLinkDiffOp instanceof DiffOpChange ) {
			$new = $siteLinkDiffOp->getNewValue();
			$newTitle = Title::newFromText( $new );

			if ( !$this->canNotifyForTitle( $newTitle ) ) {
				return false;
			}

			$old = $siteLinkDiffOp->getOldValue();
			$oldTitle = Title::newFromText( $old );

			// propably means that there was a page move
			// without keeping the old title as redirect
			if ( !$oldTitle->exists() ) {
				return false;
			}

			// even if the old page is a redirect, make sure it redirects to the new title
			if ( $oldTitle->isRedirect() ) {
				$page = WikiPage::factory( $oldTitle );
				$targetTitle = $page->getRedirectTarget();
				if ( $targetTitle && $targetTitle->equals( $newTitle ) ) {
					return false;
				}
			}

			return $newTitle;
		}

		return false;
	}

	/**
	 * Whether it's reasonable to send a notification for the title
	 *
	 * @param Title $title
	 *
	 * @return bool
	 */
	private function canNotifyForTitle( Title $title ) {
		return $title->exists() && !$title->isRedirect()
			&& MWNamespace::isContent( $title->getNamespace() );
	}

}
