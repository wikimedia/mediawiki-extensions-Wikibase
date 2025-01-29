<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliases;

use Wikibase\Repo\RestApi\Application\UseCases\GetLatestPropertyRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Services\PropertyAliasesRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyAliases {

	private GetLatestPropertyRevisionMetadata $getLatestRevisionMetadata;
	private PropertyAliasesRetriever $propertyAliasesRetriever;
	private GetPropertyAliasesValidator $validator;

	public function __construct(
		GetLatestPropertyRevisionMetadata $getLatestPropertyRevisionMetadata,
		PropertyAliasesRetriever $propertyAliasesRetriever,
		GetPropertyAliasesValidator $validator
	) {
		$this->getLatestRevisionMetadata = $getLatestPropertyRevisionMetadata;
		$this->propertyAliasesRetriever = $propertyAliasesRetriever;
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( GetPropertyAliasesRequest $request ): GetPropertyAliasesResponse {
		$propertyId = $this->validator->validateAndDeserialize( $request )->getPropertyId();

		[ $revisionId, $lastModified ] = $this->getLatestRevisionMetadata->execute( $propertyId );

		return new GetPropertyAliasesResponse(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			$this->propertyAliasesRetriever->getAliases( $propertyId ),
			$lastModified,
			$revisionId,
		);
	}

}
