<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemLabels;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Domains\Reuse\Domain\Services\BatchItemLabelsRetriever;

/**
 * @license GPL-2.0-or-later
 */
class BatchGetItemLabels {

	public function __construct( private readonly BatchItemLabelsRetriever $retriever ) {
	}

	/**
	 * This use case does not validate its request object.
	 * Validation must be added before it can be used in a context where the request is created from user input.
	 */
	public function execute( BatchGetItemLabelsRequest $request ): BatchGetItemLabelsResponse {

		$itemIds = array_map(
			fn( string $id ) => new ItemId( $id ),
			$request->itemIds
		);

		return new BatchGetItemLabelsResponse( $this->retriever->getItemLabels(
			$itemIds,
			$request->languageCodes
		) );
	}
}
