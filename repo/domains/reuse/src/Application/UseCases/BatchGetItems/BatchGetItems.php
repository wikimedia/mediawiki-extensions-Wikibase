<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItems;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Domains\Reuse\Domain\Services\ItemsBatchRetriever;

/**
 * @license GPL-2.0-or-later
 */
class BatchGetItems {

	public function __construct( private readonly ItemsBatchRetriever $itemsRetriever ) {
	}

	/**
	 * This use case does not validate its request object.
	 * Validation must be added before it can be used in a context where the request is created from user input.
	 */
	public function execute( BatchGetItemsRequest $req ): BatchGetItemsResponse {
		return new BatchGetItemsResponse( $this->itemsRetriever->getItems(
			...array_map( fn( $id ) => new ItemId( $id ), $req->itemIds )
		) );
	}

}
