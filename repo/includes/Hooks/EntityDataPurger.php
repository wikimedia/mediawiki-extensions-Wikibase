<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Hooks;

use MediaWiki\Cache\HTMLCacheUpdater;
use MediaWiki\Hook\ArticleRevisionVisibilitySetHook;
use MediaWiki\JobQueue\JobQueueGroup;
use MediaWiki\JobQueue\JobSpecification;
use MediaWiki\Logging\ManualLogEntry;
use MediaWiki\Page\Hook\PageDeleteCompleteHook;
use MediaWiki\Page\ProperPageIdentity;
use MediaWiki\Permissions\Authority;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Repo\LinkedData\EntityDataUriManager;

/**
 * @license GPL-2.0-or-later
 */
class EntityDataPurger implements ArticleRevisionVisibilitySetHook, PageDeleteCompleteHook {

	/** @var EntityIdLookup */
	private $entityIdLookup;

	/** @var EntityDataUriManager */
	private $entityDataUriManager;

	/** @var HTMLCacheUpdater */
	private $htmlCacheUpdater;

	/** @var JobQueueGroup */
	private $jobQueueGroup;

	/** @var TitleFactory */
	private $titleFactory;

	public function __construct(
		EntityIdLookup $entityIdLookup,
		EntityDataUriManager $entityDataUriManager,
		HTMLCacheUpdater $htmlCacheUpdater,
		JobQueueGroup $jobQueueGroup,
		TitleFactory $titleFactory
	) {
		$this->entityIdLookup = $entityIdLookup;
		$this->entityDataUriManager = $entityDataUriManager;
		$this->htmlCacheUpdater = $htmlCacheUpdater;
		$this->jobQueueGroup = $jobQueueGroup;
		$this->titleFactory = $titleFactory;
	}

	public static function factory(
		HTMLCacheUpdater $htmlCacheUpdater,
		JobQueueGroup $jobQueueGroup,
		TitleFactory $titleFactory,
		EntityDataUriManager $entityDataUriManager,
		EntityIdLookup $entityIdLookup
	): self {
		return new self(
			$entityIdLookup,
			$entityDataUriManager,
			$htmlCacheUpdater,
			$jobQueueGroup,
			$titleFactory
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

	/** @inheritDoc */
	public function onPageDeleteComplete(
		ProperPageIdentity $page,
		Authority $deleter,
		string $reason,
		int $pageID,
		RevisionRecord $deletedRev,
		ManualLogEntry $logEntry,
		int $archivedRevisionCount
	) {
		$title = $this->titleFactory->newFromPageIdentity( $page );
		$entityId = $this->entityIdLookup->getEntityIdForTitle( $title );
		if ( !$entityId ) {
			return;
		}

		$this->jobQueueGroup->lazyPush( new JobSpecification( 'PurgeEntityData', [
			'namespace' => $title->getNamespace(),
			'title' => $title->getDBkey(),
			'pageId' => $pageID,
			'entityId' => $entityId->getSerialization(),
		] ) );
	}
}
