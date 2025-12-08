<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemDescriptions;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Domains\Reuse\Domain\Services\BatchItemDescriptionsRetriever;

/**
 * @license GPL-2.0-or-later
 */
class BatchGetItemDescriptions {

	public function __construct( private readonly BatchItemDescriptionsRetriever $retriever ) {
	}

	/**
	 * This use case does not validate its request object.
	 * Validation must be added before it can be used in a context where the request is created from user input.
	 */
	public function execute( BatchGetItemDescriptionsRequest $request ): BatchGetItemDescriptionsResponse {
		$itemIds = array_map(
			fn( string $id ) => new ItemId( $id ),
			$request->itemIds
		);

		return new BatchGetItemDescriptionsResponse( $this->retriever->getItemDescriptions(
			$itemIds,
			$request->languageCodes
		) );
	}
}
