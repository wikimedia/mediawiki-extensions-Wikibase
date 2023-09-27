<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliasesInLanguage;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestPropertyRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Services\PropertyAliasesInLanguageRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyAliasesInLanguage {

	private GetLatestPropertyRevisionMetadata $getRevisionMetadata;

	private PropertyAliasesInLanguageRetriever $propertyAliasesRetriever;

	public function __construct(
		GetLatestPropertyRevisionMetadata $getRevisionMetadata,
		PropertyAliasesInLanguageRetriever $propertyAliasesRetriever
	) {
		$this->getRevisionMetadata = $getRevisionMetadata;
		$this->propertyAliasesRetriever = $propertyAliasesRetriever;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( GetPropertyAliasesInLanguageRequest $request ): GetPropertyAliasesInLanguageResponse {
		$propertyId = new NumericPropertyId( $request->getPropertyId() );

		[ $revisionId, $lastModified ] = $this->getRevisionMetadata->execute( $propertyId );

		return new GetPropertyAliasesInLanguageResponse(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			$this->propertyAliasesRetriever->getAliasesInLanguage( $propertyId, $request->getLanguageCode() ),
			$lastModified,
			$revisionId
		);
	}

}
