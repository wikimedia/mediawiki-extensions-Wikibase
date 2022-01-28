<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Hooks;

use HtmlCacheUpdater;
use JobQueueGroup;
use JobSpecification;
use MediaWiki\Hook\ArticleRevisionVisibilitySetHook;
use MediaWiki\Page\Hook\ArticleDeleteCompleteHook;
use Title;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Repo\LinkedData\EntityDataUriManager;

/**
 * @license GPL-2.0-or-later
 */
class EntityDataPurger implements ArticleRevisionVisibilitySetHook, ArticleDeleteCompleteHook {

	/** @var EntityIdLookup */
	private $entityIdLookup;

	/** @var EntityDataUriManager */
	private $entityDataUriManager;

	/** @var HtmlCacheUpdater */
	private $htmlCacheUpdater;

	/** @var JobQueueGroup */
	private $jobQueueGroup;

	public function __construct(
		EntityIdLookup $entityIdLookup,
		EntityDataUriManager $entityDataUriManager,
		HtmlCacheUpdater $htmlCacheUpdater,
		JobQueueGroup $jobQueueGroup
	) {
		$this->entityIdLookup = $entityIdLookup;
		$this->entityDataUriManager = $entityDataUriManager;
		$this->htmlCacheUpdater = $htmlCacheUpdater;
		$this->jobQueueGroup = $jobQueueGroup;
	}

	public static function factory(
		HtmlCacheUpdater $htmlCacheUpdater,
		JobQueueGroup $jobQueueGroup,
		EntityDataUriManager $entityDataUriManager,
		EntityIdLookup $entityIdLookup
	): self {
		return new self(
			$entityIdLookup,
			$entityDataUriManager,
			$htmlCacheUpdater,
			$jobQueueGroup
		);
	}

	/**
	 * @param Title $title
	 * @param int[] $ids
	 * @param int[][] $visibilityChangeMap
	 */
   public function onArticleRevisionVisibilitySet( $title, $ids, $visibilityChangeMap ): void {
		$entityId = $this->entityIdLookup->getEntityIdForTitle( $title );
		if ( !$entityId ) {
			return;
		}

		$urls = [];
		foreach ( $ids as $revisionId ) {
			$urls = array_merge( $urls, $this->entityDataUriManager->getPotentiallyCachedUrls(
				$entityId,
				// $ids should be int[] but MediaWiki may call with a string[], so cast to int
				(int)$revisionId
			) );
		}
		if ( $urls !== [] ) {
			$this->htmlCacheUpdater->purgeUrls( $urls );
		}
   }

	/**
	 * @param \WikiPage $wikiPage
	 * @param \User $user
	 * @param string $reason
	 * @param int $id
	 * @param \Content|null $content
	 * @param \ManualLogEntry $logEntry
	 * @param int $archivedRevisionCount
	 * @return bool|void
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
		$title = $wikiPage->getTitle();
		$entityId = $this->entityIdLookup->getEntityIdForTitle( $title );
		if ( !$entityId ) {
			return;
		}

		$this->jobQueueGroup->lazyPush( new JobSpecification( 'PurgeEntityData', [
			'namespace' => $title->getNamespace(),
			'title' => $title->getDBkey(),
			'pageId' => $id,
			'entityId' => $entityId->getSerialization(),
		] ) );
	}
}
