<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetProperty;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestPropertyRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Services\PropertyDataRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetProperty {
	private GetLatestPropertyRevisionMetadata $getLatestPropertyRevisionMetadata;
	private PropertyDataRetriever $propertyDataRetriever;
	private GetPropertyValidator $validator;

	public function __construct(
		GetLatestPropertyRevisionMetadata $getLatestPropertyRevisionMetadata,
		PropertyDataRetriever $propertyDataRetriever,
		GetPropertyValidator $validator
	) {
		$this->getLatestPropertyRevisionMetadata = $getLatestPropertyRevisionMetadata;
		$this->propertyDataRetriever = $propertyDataRetriever;
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( GetPropertyRequest $propertyRequest ): GetPropertyResponse {
		$this->validator->assertValidRequest( $propertyRequest );
		$propertyId = new NumericPropertyId( $propertyRequest->getPropertyId() );
		[ $revisionId, $lastModified ] = $this->getLatestPropertyRevisionMetadata->execute( $propertyId );

		return new GetPropertyResponse(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable Property exists
			$this->propertyDataRetriever->getPropertyData( $propertyId, $propertyRequest->getFields() ),
			$lastModified,
			$revisionId
		);
	}

}
