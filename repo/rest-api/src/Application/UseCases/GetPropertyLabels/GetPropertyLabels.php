<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabels;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestPropertyRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Services\PropertyLabelsRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyLabels {

	private GetLatestPropertyRevisionMetadata $getLatestRevisionMetadata;
	private PropertyLabelsRetriever $propertyLabelsRetriever;

	public function __construct(
		GetLatestPropertyRevisionMetadata $getLatestRevisionMetadata,
		PropertyLabelsRetriever $propertyLabelsRetriever
	) {
		$this->getLatestRevisionMetadata = $getLatestRevisionMetadata;
		$this->propertyLabelsRetriever = $propertyLabelsRetriever;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( GetPropertyLabelsRequest $request ): GetPropertyLabelsResponse {
		// TODO: validation

		$propertyId = new NumericPropertyId( $request->getPropertyId() );

		[ $revisionId, $lastModified ] = $this->getLatestRevisionMetadata->execute( $propertyId );

		return new GetPropertyLabelsResponse(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable Property validated and exists
			$this->propertyLabelsRetriever->getLabels( $propertyId ),
			$lastModified,
			$revisionId,
		);
	}
}
