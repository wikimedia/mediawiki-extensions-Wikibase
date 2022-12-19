<?php

namespace Wikibase\Client\Hooks;

use Diff\DiffOp\DiffOp;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use EchoEvent;
use MediaWiki\MediaWikiServices;
use MediaWiki\Page\RedirectLookup;
use MediaWiki\User\UserOptionsManager;
use Title;
use User;
use Wikibase\Client\NamespaceChecker;
use Wikibase\Client\RepoLinker;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\Changes\Change;
use Wikibase\Lib\Changes\ItemChange;

/**
 * Handlers for client Echo notifications
 *
 * @license GPL-2.0-or-later
 * @author Matěj Suchánek
 */
class EchoNotificationsHandlers {

	/**
	 * Type of notification
	 */
	public const NOTIFICATION_TYPE = 'page-connection';

	/**
	 * @var RepoLinker
	 */
	private $repoLinker;

	/**
	 * @var NamespaceChecker
	 */
	private $namespaceChecker;

	/** @var RedirectLookup */
	private $redirectLookup;

	/**
	 * @var UserOptionsManager
	 */
	private $userOptionsManager;

	/**
	 * @var string
	 */
	private $siteId;

	/**
	 * @var bool
	 */
	private $sendEchoNotification;

	/**
	 * @var string
	 */
	private $repoSiteName;

	/**
	 * @param RepoLinker $repoLinker
	 * @param NamespaceChecker $namespaceChecker
	 * @param UserOptionsManager $userOptionsManager
	 * @param string $siteId
	 * @param bool $sendEchoNotification
	 * @param string $repoSiteName
	 */
	public function __construct(
		RepoLinker $repoLinker,
		NamespaceChecker $namespaceChecker,
		RedirectLookup $redirectLookup,
		UserOptionsManager $userOptionsManager,
		$siteId,
		$sendEchoNotification,
		$repoSiteName
	) {
		$this->repoLinker = $repoLinker;
		$this->namespaceChecker = $namespaceChecker;
		$this->redirectLookup = $redirectLookup;
		$this->userOptionsManager = $userOptionsManager;
		$this->siteId = $siteId;
		$this->sendEchoNotification = $sendEchoNotification;
		$this->repoSiteName = $repoSiteName;
	}

	// TODO convert this to a proper hook handler class,
	// register factory with services in extension JSON file
	public static function factory(): self {
		$services = MediaWikiServices::getInstance();
		$settings = WikibaseClient::getSettings( $services );

		return new self(
			WikibaseClient::getRepoLinker( $services ),
			WikibaseClient::getNamespaceChecker( $services ),
			$services->getRedirectLookup(),
			$services->getUserOptionsManager(),
			$settings->getSetting( 'siteGlobalID' ),
			$settings->getSetting( 'sendEchoNotification' ),
			$settings->getSetting( 'repoSiteName' )
		);
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
	 * Handler for LocalUserCreated hook.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LocalUserCreated
	 * @param User $user User object that was created.
	 * @param bool $autocreated True when account was auto-created
	 */
	public static function onLocalUserCreated( User $user, $autocreated ) {
		$self = self::factory();
		$self->doLocalUserCreated( $user, $autocreated );
	}

	/**
	 * @param User $user
	 * @param bool $autocreated
	 */
	public function doLocalUserCreated( User $user, $autocreated ) {
		if ( $this->sendEchoNotification === true ) {
			$this->userOptionsManager->setOption(
				$user,
				'echo-subscriptions-web-wikibase-action',
				true
			);
		}
	}

	/**
	 * Handler for WikibaseHandleChange hook
	 * @see doWikibaseHandleChange
	 *
	 * @param Change $change
	 */
	public static function onWikibaseHandleChange( Change $change ) {
		$self = self::factory();
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
					'entity' => $entityId->getSerialization(),
				],
				'title' => $title,
				'type' => self::NOTIFICATION_TYPE,
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

			// even if the old page is a redirect, make sure it redirects to the new title (ignoring any fragments)
			$target = $this->redirectLookup->getRedirectTarget( $oldTitle );
			if ( $target && $target->createFragmentTarget( '' )
					->isSameLinkAs( $newTitle->createFragmentTarget( '' ) ) ) {
				return false;
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
			&& $this->namespaceChecker->isWikibaseEnabled( $title->getNamespace() );
	}

}
