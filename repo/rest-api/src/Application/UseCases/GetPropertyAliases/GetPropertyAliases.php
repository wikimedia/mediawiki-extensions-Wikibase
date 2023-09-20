<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliases;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestPropertyRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Services\PropertyAliasesRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyAliases {

	private GetLatestPropertyRevisionMetadata $getLatestRevisionMetadata;

	private PropertyAliasesRetriever $propertyAliasesRetriever;

	public function __construct(
		GetLatestPropertyRevisionMetadata $getLatestPropertyRevisionMetadata,
		PropertyAliasesRetriever $propertyAliasesRetriever
	) {
		$this->getLatestRevisionMetadata = $getLatestPropertyRevisionMetadata;
		$this->propertyAliasesRetriever = $propertyAliasesRetriever;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( GetPropertyAliasesRequest $request ): GetPropertyAliasesResponse {
		$propertyId = new NumericPropertyId( $request->getPropertyId() );

		[ $revisionId, $lastModified ] = $this->getLatestRevisionMetadata->execute( $propertyId );

		return new GetPropertyAliasesResponse(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			$this->propertyAliasesRetriever->getAliases( $propertyId ),
			$lastModified,
			$revisionId,
		);
	}

}
