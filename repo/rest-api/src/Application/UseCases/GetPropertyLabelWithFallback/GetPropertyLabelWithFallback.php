<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabelWithFallback;

use Wikibase\Repo\RestApi\Application\UseCases\GetLatestPropertyRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Services\PropertyLabelRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyLabelWithFallback {

	private PropertyLabelRetriever $labelRetriever;
	private GetLatestPropertyRevisionMetadata $getRevisionMetadata;
	private GetPropertyLabelWithFallbackValidator $validator;

	public function __construct(
		GetPropertyLabelWithFallbackValidator $validator,
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
	public function execute( GetPropertyLabelWithFallbackRequest $request ): GetPropertyLabelWithFallbackResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );
		$propertyId = $deserializedRequest->getPropertyId();
		$languageCode = $deserializedRequest->getLanguageCode();

		[ $revisionId, $lastModified ] = $this->getRevisionMetadata->execute( $propertyId );

		$label = $this->labelRetriever->getLabel( $propertyId, $languageCode );
		if ( !$label ) {
			throw UseCaseError::newResourceNotFound( 'label' );
		}

		return new GetPropertyLabelWithFallbackResponse( $label, $lastModified, $revisionId );
	}

}
