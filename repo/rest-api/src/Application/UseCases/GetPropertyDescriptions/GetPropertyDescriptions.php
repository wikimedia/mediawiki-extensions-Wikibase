<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyDescriptions;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Domain\Services\PropertyDescriptionsRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyDescriptions {

	private PropertyDescriptionsRetriever $propertyDescriptionsRetriever;

	public function __construct( PropertyDescriptionsRetriever $propertyDescriptionsRetriever ) {
		$this->propertyDescriptionsRetriever = $propertyDescriptionsRetriever;
	}

	public function execute( GetPropertyDescriptionsRequest $request ): GetPropertyDescriptionsResponse {
		$propertyId = new NumericPropertyId( $request->getPropertyId() );

		return new GetPropertyDescriptionsResponse(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable happy path
			$this->propertyDescriptionsRetriever->getDescriptions( $propertyId )
		);
	}
}
