<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\DataAccess;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Item;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemsBatch;
use Wikibase\Repo\Domains\Reuse\Domain\Services\ItemsBatchRetriever;

/**
 * @license GPL-2.0-or-later
 */
class EntityLookupItemsBatchRetriever implements ItemsBatchRetriever {

	public function __construct( private readonly EntityLookup $entityLookup ) {
	}

	/**
	 * This implementation just gets items from an EntityLookup one by one. There is room for optimization here.
	 */
	public function getItems( ItemId ...$ids ): ItemsBatch {
		$batch = [];
		foreach ( $ids as $id ) {
			$batch[$id->getSerialization()] = $this->getItem( $id );
		}

		return new ItemsBatch( $batch );
	}

	private function getItem( ItemId $id ): ?Item {
		/** @var \Wikibase\DataModel\Entity\Item|null $item */
		$item = $this->entityLookup->getEntity( $id );
		'@phan-var \Wikibase\DataModel\Entity\Item|null $item';

		return $item ? new Item( $item->getId() ) : null;
	}
}
