<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabel;

use Wikibase\Repo\RestApi\Application\UseCases\GetLatestPropertyRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Services\PropertyLabelRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyLabel {

	private PropertyLabelRetriever $labelRetriever;
	private GetLatestPropertyRevisionMetadata $getRevisionMetadata;
	private GetPropertyLabelValidator $validator;

	public function __construct(
		GetPropertyLabelValidator $validator,
		GetLatestPropertyRevisionMetadata $getRevisionMetadata,
		PropertyLabelRetriever $labelRetriever
	) {
		$this->labelRetriever = $labelRetriever;
		$this->getRevisionMetadata = $getRevisionMetadata;
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( GetPropertyLabelRequest $request ): GetPropertyLabelResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );
		$propertyId = $deserializedRequest->getPropertyId();
		$languageCode = $deserializedRequest->getLanguageCode();

		[ $revisionId, $lastModified ] = $this->getRevisionMetadata->execute( $propertyId );

		$label = $this->labelRetriever->getLabel( $propertyId, $languageCode );
		if ( !$label ) {
			throw new UseCaseError(
				UseCaseError::LABEL_NOT_DEFINED,
				"Property with the ID {$propertyId->getSerialization()} does not have a label in the language: {$languageCode}"
			);
		}

		return new GetPropertyLabelResponse( $label, $lastModified, $revisionId );
	}

}
