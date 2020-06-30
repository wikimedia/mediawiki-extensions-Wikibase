<?php

namespace Wikibase\Client\Hooks;

use Content;
use JobQueueGroup;
use LogEntry;
use MediaWiki\Hook\PageMoveCompleteHook;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Page\Hook\ArticleDeleteCompleteHook;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\User\UserIdentity;
use MWException;
use Psr\Log\LoggerInterface;
use Title;
use User;
use Wikibase\Client\NamespaceChecker;
use Wikibase\Client\UpdateRepo\UpdateRepoOnDelete;
use Wikibase\Client\UpdateRepo\UpdateRepoOnMove;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\Store\SiteLinkLookup;
use WikiPage;

/**
 * This class has a static interface for use with MediaWiki's hook mechanism; the static
 * handler functions will create a new instance of UpdateRepoHookHandlers and then call the
 * corresponding member function on that.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class UpdateRepoHookHandler implements PageMoveCompleteHook, ArticleDeleteCompleteHook {

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
	 * @var LoggerInterface
	 */
	private $logger;

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
	public static function newFromGlobalState() {
		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$settings = $wikibaseClient->getSettings();

		$namespaceChecker = $wikibaseClient->getNamespaceChecker();

		$repoDB = $wikibaseClient->getDatabaseDomainNameOfLocalRepo();
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
			LoggerFactory::getInstance( 'UpdateRepo' ),
			$repoDB,
			$settings->getSetting( 'siteGlobalID' ),
			$settings->getSetting( 'propagateChangesToRepo' )
		);
	}

	/**
	 * @param NamespaceChecker $namespaceChecker
	 * @param JobQueueGroup $jobQueueGroup
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param LoggerInterface $logger
	 * @param string $repoDatabase
	 * @param string $siteGlobalID
	 * @param bool $propagateChangesToRepo
	 */
	public function __construct(
		NamespaceChecker $namespaceChecker,
		JobQueueGroup $jobQueueGroup,
		SiteLinkLookup $siteLinkLookup,
		LoggerInterface $logger,
		$repoDatabase,
		$siteGlobalID,
		$propagateChangesToRepo
	) {
		$this->namespaceChecker = $namespaceChecker;
		$this->jobQueueGroup = $jobQueueGroup;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->logger = $logger;

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
	 * After a page has been deleted also update the item on the repo.
	 * This only works if there's a user account with the same name on the repo.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleDeleteComplete
	 *
	 * @param WikiPage $wikiPage WikiPage that was deleted
	 * @param User $user User that deleted the article
	 * @param string $reason Reason the article was deleted
	 * @param int $id ID of the article that was deleted
	 * @param Content|null $content Content of the deleted page (or null, when deleting a broken page)
	 * @param \ManualLogEntry $logEntry ManualLogEntry used to record the deletion
	 * @param int $archivedRevisionCount Number of revisions archived during the deletion
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onArticleDeleteComplete(
		$wikiPage,
		$user,
		$reason,
		$id,
		$content,
		$logEntry,
		$archivedRevisionCount
	) {
		if ( $this->propagateChangesToRepo !== true ) {
			return true;
		}

		$updateRepo = new UpdateRepoOnDelete(
			$this->repoDatabase,
			$this->siteLinkLookup,
			$this->logger,
			$user,
			$this->siteGlobalID,
			$wikiPage->getTitle()
		);

		if ( !$updateRepo->isApplicable() ) {
			return true;
		}

		try {
			$updateRepo->injectJob( $this->jobQueueGroup );

			// To be able to find out about this in the ArticleDeleteAfter hook
			// @phan-suppress-next-line PhanUndeclaredProperty Dynamic property
			$wikiPage->getTitle()->wikibasePushedDeleteToRepo = true;
		} catch ( MWException $e ) {
			// This is not a reason to let an exception bubble up, we just
			// show a message to the user that the Wikibase item needs to be
			// manually updated.
			wfLogWarning( $e->getMessage() );

			$this->logger->debug(
				'{method}: Failed to inject job: "{msg}"!',
				[
					'method' => __METHOD__,
					'msg' => $e->getMessage()
				]
			);

		}

		return true;
	}

	/**
	 * After a page has been moved also update the item on the repo.
	 * This only works if there's a user account with the same name on the repo.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageMoveComplete
	 *
	 * @param LinkTarget $oldLinkTarget
	 * @param LinkTarget $newLinkTarget
	 * @param UserIdentity $userIdentity
	 * @param int $pageId database ID of the page that's been moved
	 * @param int $redirid ID of the created redirect
	 * @param string $reason
	 * @param RevisionRecord $revisionRecord revision created by the move
	 *
	 * @return bool
	 */
	public function onPageMoveComplete(
		$oldLinkTarget,
		$newLinkTarget,
		$userIdentity,
		$pageId,
		$redirid,
		$reason,
		$revisionRecord
	) {
		if ( !$this->isWikibaseEnabled( $newLinkTarget->getNamespace() ) ) {
			return true;
		}

		if ( $this->propagateChangesToRepo !== true ) {
			return true;
		}

		$old = Title::newFromLinkTarget( $oldLinkTarget );
		$nt = Title::newFromLinkTarget( $newLinkTarget );
		$user = User::newFromIdentity( $userIdentity );

		$updateRepo = new UpdateRepoOnMove(
			$this->repoDatabase,
			$this->siteLinkLookup,
			$this->logger,
			$user,
			$this->siteGlobalID,
			$old,
			$nt
		);

		if ( !$updateRepo->isApplicable() ) {
			return true;
		}

		try {
			$updateRepo->injectJob( $this->jobQueueGroup );

			// To be able to find out about this in the SpecialMovepageAfterMove hook
			// @phan-suppress-next-line PhanUndeclaredProperty Dynamic property
			$nt->wikibasePushedMoveToRepo = true;
		} catch ( MWException $e ) {
			// This is not a reason to let an exception bubble up, we just
			// show a message to the user that the Wikibase item needs to be
			// manually updated.
			wfLogWarning( $e->getMessage() );

			$this->logger->debug(
				'{method}: Failed to inject job: "{msg}"!',
				[
					'method' => __METHOD__,
					'msg' => $e->getMessage()
				]
			);
		}

		return true;
	}

}
