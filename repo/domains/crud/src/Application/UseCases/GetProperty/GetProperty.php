<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetProperty;

use Wikibase\Repo\RestApi\Application\UseCases\GetLatestPropertyRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Services\PropertyPartsRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetProperty {
	private GetLatestPropertyRevisionMetadata $getLatestPropertyRevisionMetadata;
	private PropertyPartsRetriever $propertyPartsRetriever;
	private GetPropertyValidator $validator;

	public function __construct(
		GetLatestPropertyRevisionMetadata $getLatestPropertyRevisionMetadata,
		PropertyPartsRetriever $propertyPartsRetriever,
		GetPropertyValidator $validator
	) {
		$this->getLatestPropertyRevisionMetadata = $getLatestPropertyRevisionMetadata;
		$this->propertyPartsRetriever = $propertyPartsRetriever;
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( GetPropertyRequest $propertyRequest ): GetPropertyResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $propertyRequest );
		[ $revisionId, $lastModified ] = $this->getLatestPropertyRevisionMetadata->execute( $deserializedRequest->getPropertyId() );

		return new GetPropertyResponse(
			$this->propertyPartsRetriever->getPropertyParts( // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
				$deserializedRequest->getPropertyId(),
				$deserializedRequest->getPropertyFields()
			),
			$lastModified,
			$revisionId
		);
	}

}
