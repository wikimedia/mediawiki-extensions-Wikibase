<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers;

use GraphQL\Deferred;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItems\BatchGetItems;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItems\BatchGetItemsRequest;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Item;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemsBatch;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors\ItemNotFound;

/**
 * @license GPL-2.0-or-later
 */
class ItemResolver {
	private array $itemsToFetch = [];
	private ?ItemsBatch $itemsBatch = null;

	public function __construct( private readonly BatchGetItems $batchGetItems ) {
	}

	public function resolveItem( string $itemId ): Deferred {
		$this->itemsToFetch[] = $itemId;

		/**
		 * @throws ItemNotFound
		 */
		return new Deferred( function() use ( $itemId ): Item {
			if ( !$this->itemsBatch ) {
				$this->itemsBatch = $this->batchGetItems
					->execute( new BatchGetItemsRequest( $this->itemsToFetch ) )
					->itemsBatch;
			}

			$item = $this->itemsBatch->getItem( new ItemId( $itemId ) );
			if ( !$item ) {
				throw new ItemNotFound( $itemId );
			}

			return $item;
		} );
	}
}
