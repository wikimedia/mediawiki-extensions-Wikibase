<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers;

use GraphQL\Deferred;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItems\BatchGetItems;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItems\BatchGetItemsRequest;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemsBatch;

/**
 * @license GPL-2.0-or-later
 */
class ItemResolver {
	private array $itemsToFetch = [];
	private ?ItemsBatch $itemsBatch = null;

	public function __construct(
		private readonly BatchGetItems $batchGetItems
	) {
	}

	public function resolve( string $itemId ): Deferred {
		$this->itemsToFetch[] = $itemId;

		return new Deferred( function() use ( $itemId ) {
			if ( !$this->itemsBatch ) {
				$this->itemsBatch = $this->batchGetItems
					->execute( new BatchGetItemsRequest( $this->itemsToFetch ) )
					->itemsBatch;
			}

			$item = $this->itemsBatch->getItem( new ItemId( $itemId ) );

			return $item
				? [ 'id' => $item->id->getSerialization() ] // a serializer goes here eventually
				: null;
		} );
	}
}
