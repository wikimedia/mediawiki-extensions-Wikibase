<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyDescription;

use Wikibase\Repo\RestApi\Application\UseCases\GetLatestPropertyRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Services\PropertyDescriptionRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyDescription {

	private GetPropertyDescriptionValidator $validator;
	private GetLatestPropertyRevisionMetadata $getRevisionMetadata;
	private PropertyDescriptionRetriever $descriptionRetriever;

	public function __construct(
		GetPropertyDescriptionValidator $validator,
		GetLatestPropertyRevisionMetadata $getRevisionMetadata,
		PropertyDescriptionRetriever $descriptionRetriever
	) {
		$this->validator = $validator;
		$this->getRevisionMetadata = $getRevisionMetadata;
		$this->descriptionRetriever = $descriptionRetriever;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( GetPropertyDescriptionRequest $request ): GetPropertyDescriptionResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );
		$propertyId = $deserializedRequest->getPropertyId();
		$languageCode = $deserializedRequest->getLanguageCode();

		[ $revisionId, $lastModified ] = $this->getRevisionMetadata->execute( $propertyId );

		$description = $this->descriptionRetriever->getDescription( $propertyId, $languageCode );
		if ( !$description ) {
			throw new UseCaseError(
				UseCaseError::DESCRIPTION_NOT_DEFINED,
				"Property with the ID {$propertyId->getSerialization()} does not have a description in the language: $languageCode"
			);
		}

		return new GetPropertyDescriptionResponse( $description, $lastModified, $revisionId );
	}
}
