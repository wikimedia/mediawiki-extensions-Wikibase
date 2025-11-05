<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetPropertyLabels;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\Domains\Reuse\Domain\Services\BatchPropertyLabelsRetriever;

/**
 * @license GPL-2.0-or-later
 */
class BatchGetPropertyLabels {

	public function __construct( private readonly BatchPropertyLabelsRetriever $labelsRetriever ) {
	}

	/**
	 * This use case does not validate its request object.
	 * Validation must be added before it can be used in a context where the request is created from user input.
	 */
	public function execute( BatchGetPropertyLabelsRequest $request ): BatchGetPropertyLabelsResponse {

		$propertyIds = array_map(
			fn( string $id ) => new NumericPropertyId( $id ),
			$request->propertyIds
		);

		return new BatchGetPropertyLabelsResponse( $this->labelsRetriever->getPropertyLabels(
			$propertyIds,
			$request->languageCodes
		) );
	}
}
