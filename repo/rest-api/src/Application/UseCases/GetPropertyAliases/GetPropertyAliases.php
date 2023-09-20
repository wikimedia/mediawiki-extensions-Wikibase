<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliases;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Domain\Services\PropertyAliasesRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyAliases {

	private PropertyAliasesRetriever $propertyAliasesRetriever;

	public function __construct( PropertyAliasesRetriever $propertyAliasesRetriever ) {
		$this->propertyAliasesRetriever = $propertyAliasesRetriever;
	}

	public function execute( GetPropertyAliasesRequest $request ): GetPropertyAliasesResponse {
		$propertyId = new NumericPropertyId( $request->getPropertyId() );

		return new GetPropertyAliasesResponse(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			$this->propertyAliasesRetriever->getAliases( $propertyId )
		);
	}

}
