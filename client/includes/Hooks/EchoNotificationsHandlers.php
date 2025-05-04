<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Hooks;

use Diff\DiffOp\DiffOp;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use MediaWiki\Auth\Hook\LocalUserCreatedHook;
use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\Page\RedirectLookup;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\Title\Title;
use MediaWiki\User\Options\UserOptionsManager;
use MediaWiki\User\User;
use MediaWiki\User\UserIdentityLookup;
use MediaWiki\User\UserIdentityValue;
use Wikibase\Client\NamespaceChecker;
use Wikibase\Client\RepoLinker;
use Wikibase\Lib\Changes\Change;
use Wikibase\Lib\Changes\ItemChange;
use Wikibase\Lib\SettingsArray;
use Wikimedia\IPUtils;

/**
 * Handlers for client Echo notifications
 *
 * @license GPL-2.0-or-later
 * @author Matěj Suchánek
 */
class EchoNotificationsHandlers implements WikibaseHandleChangeHook, LocalUserCreatedHook {

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

	private UserIdentityLookup $userIdentityLookup;

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

	public function __construct(
		RepoLinker $repoLinker,
		NamespaceChecker $namespaceChecker,
		RedirectLookup $redirectLookup,
		UserIdentityLookup $userIdentityLookup,
		UserOptionsManager $userOptionsManager,
		string $siteId,
		bool $sendEchoNotification,
		string $repoSiteName
	) {
		$this->repoLinker = $repoLinker;
		$this->namespaceChecker = $namespaceChecker;
		$this->redirectLookup = $redirectLookup;
		$this->userIdentityLookup = $userIdentityLookup;
		$this->userOptionsManager = $userOptionsManager;
		$this->siteId = $siteId;
		$this->sendEchoNotification = $sendEchoNotification;
		$this->repoSiteName = $repoSiteName;
	}

	public static function factory(
		RedirectLookup $redirectLookup,
		UserIdentityLookup $userIdentityLookup,
		UserOptionsManager $userOptionsManager,
		NamespaceChecker $namespaceChecker,
		RepoLinker $repoLinker,
		SettingsArray $settings
	): self {
		return new self(
			$repoLinker,
			$namespaceChecker,
			$redirectLookup,
			$userIdentityLookup,
			$userOptionsManager,
			$settings->getSetting( 'siteGlobalID' ),
			$settings->getSetting( 'sendEchoNotification' ),
			$settings->getSetting( 'repoSiteName' )
		);
	}

	/**
	 * Handler for LocalUserCreated hook.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LocalUserCreated
	 * @param User $user User object that was created.
	 * @param bool $autocreated True when account was auto-created
	 */
	public function onLocalUserCreated( $user, $autocreated ) {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ) {
			return;
		}

		if ( $this->sendEchoNotification === true ) {
			$this->userOptionsManager->setOption(
				$user,
				'echo-subscriptions-web-wikibase-action',
				true
			);
		}
	}

	public function onWikibaseHandleChange( Change $change, array $rootJobParams = [] ): void {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ) {
			return;
		}
		$this->doWikibaseHandleChange( $change );
	}

	public function doWikibaseHandleChange( Change $change ): bool {
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
			$userName = $metadata['user_text'];
			$agent = $this->userIdentityLookup->getUserIdentityByName( $userName );
			if ( $agent === null && IPUtils::isValid( $userName ) ) {
				$agent = UserIdentityValue::newAnonymous( $userName );
			}
			Event::create( [
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

			// probably means that there was a page move
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
