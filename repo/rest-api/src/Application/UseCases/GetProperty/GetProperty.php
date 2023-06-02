<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetProperty;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Domain\Services\PropertyDataRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetProperty {

	private PropertyDataRetriever $propertyDataRetriever;

	public function __construct( PropertyDataRetriever $propertyDataRetriever ) {
		$this->propertyDataRetriever = $propertyDataRetriever;
	}

	public function execute( GetPropertyRequest $request ): GetPropertyResponse {
		$propertyId = new NumericPropertyId( $request->getPropertyId() );

		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable Property exists
		return new GetPropertyResponse( $this->propertyDataRetriever->getPropertyData( $propertyId ) );
	}

}
