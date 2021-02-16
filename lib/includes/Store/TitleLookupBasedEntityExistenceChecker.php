<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Store;

use MediaWiki\Cache\LinkBatchFactory;
use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
class TitleLookupBasedEntityExistenceChecker implements EntityExistenceChecker {

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	/** @var LinkBatchFactory */
	private $linkBatchFactory;

	public function __construct(
		EntityTitleLookup $titleLookup,
		LinkBatchFactory $linkBatchFactory
	) {
		$this->titleLookup = $titleLookup;
		$this->linkBatchFactory = $linkBatchFactory;
	}

	public function exists( EntityId $id ): bool {
		$title = $this->titleLookup->getTitleForId( $id );

		return $title !== null && $title->isKnown();
	}

	public function existsBatch( array $ids ): array {
		$titles = $this->titleLookup->getTitlesForIds( $ids );

		$linkBatch = $this->linkBatchFactory->newLinkBatch( array_filter( $titles ) );
		$linkBatch->setCaller( __METHOD__ );
		$linkBatch->execute();

		$ret = [];
		foreach ( $titles as $serialization => $title ) {
			$ret[$serialization] = $title !== null && $title->isKnown();
		}
		return $ret;
	}

}
