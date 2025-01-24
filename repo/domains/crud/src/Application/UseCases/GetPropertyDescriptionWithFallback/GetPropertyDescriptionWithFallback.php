<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyDescriptionWithFallback;

use Wikibase\Repo\RestApi\Application\UseCases\GetLatestPropertyRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Services\PropertyDescriptionWithFallbackRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyDescriptionWithFallback {

	private GetPropertyDescriptionWithFallbackValidator $validator;
	private GetLatestPropertyRevisionMetadata $getRevisionMetadata;
	private PropertyDescriptionWithFallbackRetriever $descriptionRetriever;

	public function __construct(
		GetPropertyDescriptionWithFallbackValidator $validator,
		GetLatestPropertyRevisionMetadata $getRevisionMetadata,
		PropertyDescriptionWithFallbackRetriever $descriptionRetriever
	) {
		$this->validator = $validator;
		$this->getRevisionMetadata = $getRevisionMetadata;
		$this->descriptionRetriever = $descriptionRetriever;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( GetPropertyDescriptionWithFallbackRequest $request ): GetPropertyDescriptionWithFallbackResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );
		$propertyId = $deserializedRequest->getPropertyId();
		$languageCode = $deserializedRequest->getLanguageCode();

		[ $revisionId, $lastModified ] = $this->getRevisionMetadata->execute( $propertyId );

		$description = $this->descriptionRetriever->getDescription( $propertyId, $languageCode );
		if ( !$description ) {
			throw UseCaseError::newResourceNotFound( 'description' );
		}

		return new GetPropertyDescriptionWithFallbackResponse( $description, $lastModified, $revisionId );
	}
}
