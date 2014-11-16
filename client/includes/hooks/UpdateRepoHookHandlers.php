<?php

namespace Wikibase\Client\Hooks;

use JobQueueGroup;
use User;
use Title;
use WikiPage;
use Content;
use ManualLogEntry;
use Wikibase\Client\WikibaseClient;
use Wikibase\NamespaceChecker;
use Wikibase\SettingsArray;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Client\UpdateRepo\UpdateRepo;
use Wikibase\Client\UpdateRepo\UpdateRepoOnDelete;
use Wikibase\Client\UpdateRepo\UpdateRepoOnMove;

/**
 * This class has a static interface for use with MediaWiki's hook mechanism; the static
 * handler functions will create a new instance of UpdateRepoHookHandlers and then call the
 * corresponding member function on that.
 *
 * @since 0.5.
 *
 * @license GPL 2+
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
	 * @return UpdateRepoHookHandlers|boolean
	 */
	private static function newFromGlobalState() {
		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$settings = $wikibaseClient->getSettings();

		$namespaceChecker = $wikibaseClient->getNamespaceChecker();

		$repoDB = $settings->getSetting( 'repoDatabase' );
		$jobQueueGroup = JobQueueGroup::singleton( $repoDB );

		if ( !$jobQueueGroup ) {
			wfLogWarning( "Failed to acquire a JobQueueGroup for $repoDB" );
			wfProfileOut( __METHOD__ );
			return true;
		}

		$siteLinkLookup = $wikibaseClient->getStore()->getSiteLinkTable();

		return new UpdateRepoHookHandlers(
			$namespaceChecker,
			$jobQueueGroup,
			$siteLinkLookup,
			$settings
		);
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
	 * After a page has been deleted also update the item on the repo
	 * This only works with CentralAuth
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleDeleteComplete
	 *
	 * @param WikiPage $article
	 * @param User $user
	 * @param string $reason
	 * @param int $id id of the article that was deleted
	 * @param Content $content
	 * @param ManualLogEntry $logEntry
	 *
	 * @return bool
	 */
	public static function onArticleDeleteComplete(
		WikiPage &$article,
		User &$user,
		$reason,
		$id,
		Content $content,
		ManualLogEntry $logEntry
	) {
		$handler = self::newFromGlobalState();

		if ( $handler ) {
			$handler->doArticleDeleteComplete( $article->getTitle(), $user );
		}

		return true;
	}

	public function __construct(
		NamespaceChecker $namespaceChecker,
		JobQueueGroup $jobQueueGroup,
		SiteLinkLookup $siteLinkLookup,
		SettingsArray $settings
	) {

		$this->namespaceChecker = $namespaceChecker;
		$this->jobQueueGroup = $jobQueueGroup;
		$this->siteLinkLookup = $siteLinkLookup;

		$this->repoDatabase = $settings->getSetting( 'repoDatabase' );
		$this->siteGlobalID = $settings->getSetting( 'siteGlobalID' );
		$this->propagateChangesToRepo = $settings->getSetting( 'propagateChangesToRepo' );
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
	 * Whether a given UpdateRepo should be pushed to the repo
	 * 
	 * @param UpdateRepo $updateRepo
	 * @return bool
	 */
	private function shouldBePushed( UpdateRepo $updateRepo ) {
		return $updateRepo->getEntityId() && $updateRepo->userIsValidOnRepo();
	}

	/**
	 * @param Title $title
	 * @param User $user
	 *
	 * @return bool
	 */
	private function doArticleDeleteComplete( Title $title, User $user ) {
		if ( !$this->isWikibaseEnabled( $title->getNamespace() ) ) {
			// shorten out
			return true;
		}

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

		if ( !$this->shouldBePushed( $updateRepo ) ) {
			return true;
		}

		try {
			$updateRepo->injectJob( $this->jobQueueGroup );

			// To be able to find out about this in the ArticleDeleteAfter hook
			$title->wikibasePushedDeleteToRepo = true;
		} catch( MWException $e ) {
			// This is not a reason to let an exception bubble up, we just
			// show a message to the user that the Wikibase item needs to be
			// manually updated.
			wfLogWarning( $e->getMessage() );
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
	private function doTitleMoveComplete( Title $oldTitle, Title $newTitle, User $user ) {
		if ( !$this->isWikibaseEnabled( $oldTitle->getNamespace() )
			&& !$this->isWikibaseEnabled( $newTitle->getNamespace() ) ) {
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

		if ( !$this->shouldBePushed( $updateRepo ) ) {
			return true;
		}

		try {
			$updateRepo->injectJob( $this->jobQueueGroup );

			// To be able to find out about this in the SpecialMovepageAfterMove hook
			$newTitle->wikibasePushedMoveToRepo = true;
		} catch( MWException $e ) {
			// This is not a reason to let an exception bubble up, we just
			// show a message to the user that the Wikibase item needs to be
			// manually updated.
			wfLogWarning( $e->getMessage() );
		}

		return true;
	}
}
