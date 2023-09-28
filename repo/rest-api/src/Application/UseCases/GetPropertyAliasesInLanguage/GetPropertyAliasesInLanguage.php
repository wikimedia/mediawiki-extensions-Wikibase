<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliasesInLanguage;

use Wikibase\Repo\RestApi\Application\UseCases\GetLatestPropertyRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Services\PropertyAliasesInLanguageRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyAliasesInLanguage {

	private GetPropertyAliasesInLanguageValidator $validator;
	private GetLatestPropertyRevisionMetadata $getRevisionMetadata;
	private PropertyAliasesInLanguageRetriever $propertyAliasesRetriever;

	public function __construct(
		GetPropertyAliasesInLanguageValidator $validator,
		GetLatestPropertyRevisionMetadata $getRevisionMetadata,
		PropertyAliasesInLanguageRetriever $propertyAliasesRetriever
	) {
		$this->validator = $validator;
		$this->getRevisionMetadata = $getRevisionMetadata;
		$this->propertyAliasesRetriever = $propertyAliasesRetriever;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( GetPropertyAliasesInLanguageRequest $request ): GetPropertyAliasesInLanguageResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );
		$propertyId = $deserializedRequest->getPropertyId();
		$languageCode = $deserializedRequest->getLanguageCode();

		[ $revisionId, $lastModified ] = $this->getRevisionMetadata->execute( $propertyId );

		$aliases = $this->propertyAliasesRetriever->getAliasesInLanguage( $propertyId, $languageCode );
		if ( !$aliases ) {
			throw new UseCaseError(
				UseCaseError::ALIASES_NOT_DEFINED,
				"Property with the ID $propertyId does not have aliases in the language: $languageCode"
			);
		}

		return new GetPropertyAliasesInLanguageResponse( $aliases, $lastModified, $revisionId );
	}

}
