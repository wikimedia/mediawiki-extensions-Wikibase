<?php

namespace Wikibase\Client\Hooks;

use Content;
use JobQueueGroup;
use LogEntry;
use MWException;
use Title;
use User;
use Wikibase\Client\UpdateRepo\UpdateRepoOnDelete;
use Wikibase\Client\UpdateRepo\UpdateRepoOnMove;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Client\NamespaceChecker;
use WikiPage;

/**
 * This class has a static interface for use with MediaWiki's hook mechanism; the static
 * handler functions will create a new instance of UpdateRepoHookHandlers and then call the
 * corresponding member function on that.
 *
 * @license GPL-2.0+
 * @author Marius Hoch < hoo@online.de >
 */
class UpdateRepoHookHandlers {

	/**
	 * @var NamespaceChecker
	 */
	private $namespaceChecker;

	/**
	 * @var JobQueueGroup
	 */
	private $jobQueueGroup;

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @var string
	 */
	private $repoDatabase;

	/**
	 * @var string
	 */
	private $siteGlobalID;

	/**
	 * @var bool
	 */
	private $propagateChangesToRepo;

	/**
	 * @return self|null
	 */
	private static function newFromGlobalState() {
		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$settings = $wikibaseClient->getSettings();

		$namespaceChecker = $wikibaseClient->getNamespaceChecker();

		$repoDB = $settings->getSetting( 'repoDatabase' );
		$jobQueueGroup = JobQueueGroup::singleton( $repoDB );

		if ( !$jobQueueGroup ) {
			wfLogWarning( "Failed to acquire a JobQueueGroup for $repoDB" );
			return null;
		}

		$siteLinkLookup = $wikibaseClient->getStore()->getSiteLinkLookup();

		return new self(
			$namespaceChecker,
			$jobQueueGroup,
			$siteLinkLookup,
			$settings->getSetting( 'repoDatabase' ),
			$settings->getSetting( 'siteGlobalID' ),
			$settings->getSetting( 'propagateChangesToRepo' )
		);
	}

	/**
	 * After a page has been moved also update the item on the repo.
	 * This only works if there's a user account with the same name on the repo.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleMoveComplete
	 *
	 * @param Title $oldTitle
	 * @param Title $newTitle
	 * @param User $user
	 * @param integer $pageId database ID of the page that's been moved
	 * @param integer $redirectId database ID of the created redirect
	 * @param string $reason
	 *
	 * @return bool
	 */
	public static function onTitleMoveComplete(
		Title $oldTitle,
		Title $newTitle,
		User $user,
		$pageId,
		$redirectId,
		$reason
	) {
		$handler = self::newFromGlobalState();

		if ( $handler ) {
			$handler->doTitleMoveComplete( $oldTitle, $newTitle, $user );
		}

		return true;
	}

	/**
	 * After a page has been deleted also update the item on the repo.
	 * This only works if there's a user account with the same name on the repo.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleDeleteComplete
	 *
	 * @param WikiPage &$wikiPage
	 * @param User &$user
	 * @param string $reason
	 * @param int $id id of the article that was deleted
	 * @param Content|null $content
	 * @param LogEntry $logEntry
	 */
	public static function onArticleDeleteComplete(
		WikiPage &$wikiPage,
		User &$user,
		$reason,
		$id,
		Content $content = null,
		LogEntry $logEntry
	) {
		$handler = self::newFromGlobalState();

		if ( $handler ) {
			$handler->doArticleDeleteComplete( $wikiPage->getTitle(), $user );
		}
	}

	/**
	 * @param NamespaceChecker $namespaceChecker
	 * @param JobQueueGroup $jobQueueGroup
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param string $repoDatabase
	 * @param string $siteGlobalID
	 * @param bool $propagateChangesToRepo
	 */
	public function __construct(
		NamespaceChecker $namespaceChecker,
		JobQueueGroup $jobQueueGroup,
		SiteLinkLookup $siteLinkLookup,
		$repoDatabase,
		$siteGlobalID,
		$propagateChangesToRepo
	) {
		$this->namespaceChecker = $namespaceChecker;
		$this->jobQueueGroup = $jobQueueGroup;
		$this->siteLinkLookup = $siteLinkLookup;

		$this->repoDatabase = $repoDatabase;
		$this->siteGlobalID = $siteGlobalID;
		$this->propagateChangesToRepo = $propagateChangesToRepo;
	}

	/**
	 * @see NamespaceChecker::isWikibaseEnabled
	 *
	 * @param int $namespace
	 *
	 * @return bool
	 */
	private function isWikibaseEnabled( $namespace ) {
		return $this->namespaceChecker->isWikibaseEnabled( $namespace );
	}

	/**
	 * @param Title $title
	 * @param User $user
	 *
	 * @return bool
	 */
	public function doArticleDeleteComplete( Title $title, User $user ) {
		if ( $this->propagateChangesToRepo !== true ) {
			return true;
		}

		$updateRepo = new UpdateRepoOnDelete(
			$this->repoDatabase,
			$this->siteLinkLookup,
			$user,
			$this->siteGlobalID,
			$title
		);

		if ( !$updateRepo->isApplicable() ) {
			return true;
		}

		try {
			$updateRepo->injectJob( $this->jobQueueGroup );

			// To be able to find out about this in the ArticleDeleteAfter hook
			$title->wikibasePushedDeleteToRepo = true;
		} catch ( MWException $e ) {
			// This is not a reason to let an exception bubble up, we just
			// show a message to the user that the Wikibase item needs to be
			// manually updated.
			wfLogWarning( $e->getMessage() );
			wfDebugLog( 'UpdateRepo', "OnDelete: Failed to inject job: " . $e->getMessage() );
		}

		return true;
	}

	/**
	 * @param Title $oldTitle
	 * @param Title $newTitle
	 * @param User $user
	 *
	 * @return bool
	 */
	public function doTitleMoveComplete( Title $oldTitle, Title $newTitle, User $user ) {
		if ( !$this->isWikibaseEnabled( $newTitle->getNamespace() ) ) {
			return true;
		}

		if ( $this->propagateChangesToRepo !== true ) {
			return true;
		}

		$updateRepo = new UpdateRepoOnMove(
			$this->repoDatabase,
			$this->siteLinkLookup,
			$user,
			$this->siteGlobalID,
			$oldTitle,
			$newTitle
		);

		if ( !$updateRepo->isApplicable() ) {
			return true;
		}

		try {
			$updateRepo->injectJob( $this->jobQueueGroup );

			// To be able to find out about this in the SpecialMovepageAfterMove hook
			$newTitle->wikibasePushedMoveToRepo = true;
		} catch ( MWException $e ) {
			// This is not a reason to let an exception bubble up, we just
			// show a message to the user that the Wikibase item needs to be
			// manually updated.
			wfLogWarning( $e->getMessage() );
			wfDebugLog( 'UpdateRepo', "OnMove: Failed to inject job: " . $e->getMessage() );
		}

		return true;
	}

}
