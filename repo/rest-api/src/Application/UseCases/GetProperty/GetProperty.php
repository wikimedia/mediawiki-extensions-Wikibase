<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetProperty;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestPropertyRevisionMetadata;
use Wikibase\Repo\RestApi\Domain\Services\PropertyDataRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetProperty {
	private GetLatestPropertyRevisionMetadata $getLatestPropertyRevisionMetadata;
	private PropertyDataRetriever $propertyDataRetriever;

	public function __construct(
		GetLatestPropertyRevisionMetadata $getLatestPropertyRevisionMetadata,
		PropertyDataRetriever $propertyDataRetriever
	) {
		$this->getLatestPropertyRevisionMetadata = $getLatestPropertyRevisionMetadata;
		$this->propertyDataRetriever = $propertyDataRetriever;
	}

	public function execute( GetPropertyRequest $request ): GetPropertyResponse {
		$propertyId = new NumericPropertyId( $request->getPropertyId() );
		[ $revisionId, $lastModified ] = $this->getLatestPropertyRevisionMetadata->execute( $propertyId );

		return new GetPropertyResponse(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable Property exists
			$this->propertyDataRetriever->getPropertyData( $propertyId ),
			$lastModified,
			$revisionId
		);
	}

}
