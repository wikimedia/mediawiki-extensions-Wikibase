<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Domain\Services\PropertyStatementsRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyStatements {

	private PropertyStatementsRetriever $statementsRetriever;

	public function __construct( PropertyStatementsRetriever $statementsRetriever ) {
		$this->statementsRetriever = $statementsRetriever;
	}

	public function execute( GetPropertyStatementsRequest $request ): GetPropertyStatementsResponse {
		$subjectPropertyId = new NumericPropertyId( $request->getSubjectPropertyId() );
		$requestedFilterPropertyId = $request->getFilterPropertyId();
		$filterPropertyId = $requestedFilterPropertyId ? new NumericPropertyId( $requestedFilterPropertyId ) : null;

		return new GetPropertyStatementsResponse(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable Property exists
			$this->statementsRetriever->getStatements( $subjectPropertyId, $filterPropertyId )
		);
	}

}
