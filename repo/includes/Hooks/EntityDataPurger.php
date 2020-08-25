<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Hooks;

use HtmlCacheUpdater;
use MediaWiki\Hook\ArticleRevisionVisibilitySetHook;
use Title;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Repo\LinkedData\EntityDataUriManager;
use Wikibase\Repo\WikibaseRepo;

/**
 * @license GPL-2.0-or-later
 */
class EntityDataPurger implements ArticleRevisionVisibilitySetHook {

	/** @var EntityIdLookup */
	private $entityIdLookup;

	/** @var EntityDataUriManager */
	private $entityDataUriManager;

	/** @var HtmlCacheUpdater */
	private $htmlCacheUpdater;

	public function __construct(
		EntityIdLookup $entityIdLookup,
		EntityDataUriManager $entityDataUriManager,
		HtmlCacheUpdater $htmlCacheUpdater
	) {
		$this->entityIdLookup = $entityIdLookup;
		$this->entityDataUriManager = $entityDataUriManager;
		$this->htmlCacheUpdater = $htmlCacheUpdater;
	}

	public static function factory( HtmlCacheUpdater $htmlCacheUpdater ): self {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		return new self(
			$wikibaseRepo->getEntityIdLookup(),
			$wikibaseRepo->getEntityDataUriManager(),
			$htmlCacheUpdater
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

}
