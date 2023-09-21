<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyDescriptions;

use Wikibase\Repo\RestApi\Application\UseCases\GetLatestPropertyRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Services\PropertyDescriptionsRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyDescriptions {

	private GetLatestPropertyRevisionMetadata $getLatestRevisionMetadata;
	private PropertyDescriptionsRetriever $propertyDescriptionsRetriever;
	private GetPropertyDescriptionsValidator $validator;

	public function __construct(
		GetLatestPropertyRevisionMetadata $getLatestRevisionMetadata,
		PropertyDescriptionsRetriever $propertyDescriptionsRetriever,
		GetPropertyDescriptionsValidator $validator
	) {
		$this->getLatestRevisionMetadata = $getLatestRevisionMetadata;
		$this->propertyDescriptionsRetriever = $propertyDescriptionsRetriever;
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( GetPropertyDescriptionsRequest $request ): GetPropertyDescriptionsResponse {
		$propertyId = $this->validator->validateAndDeserialize( $request )->getPropertyId();

		[ $revisionId, $lastModified ] = $this->getLatestRevisionMetadata->execute( $propertyId );

		return new GetPropertyDescriptionsResponse(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable happy path
			$this->propertyDescriptionsRetriever->getDescriptions( $propertyId ),
			$lastModified,
			$revisionId,
		);
	}
}
