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

	public function execute( BatchGetItemsRequest $req ): BatchGetItemsResponse {
		// TODO validate request
		return new BatchGetItemsResponse( $this->itemsRetriever->getItems(
			...array_map( fn( $id ) => new ItemId( $id ), $req->itemIds )
		) );
	}

}
