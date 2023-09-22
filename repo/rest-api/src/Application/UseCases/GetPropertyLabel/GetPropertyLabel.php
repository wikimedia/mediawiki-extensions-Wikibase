<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabel;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestPropertyRevisionMetadata;
use Wikibase\Repo\RestApi\Domain\Services\PropertyLabelRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyLabel {

	private PropertyLabelRetriever $labelRetriever;
	private GetLatestPropertyRevisionMetadata $getRevisionMetadata;

	public function __construct(
		GetLatestPropertyRevisionMetadata $getRevisionMetadata,
		PropertyLabelRetriever $labelRetriever
	) {
		$this->labelRetriever = $labelRetriever;
		$this->getRevisionMetadata = $getRevisionMetadata;
	}

	public function execute( GetPropertyLabelRequest $request ): GetPropertyLabelResponse {
		$propertyId = new NumericPropertyId( $request->getPropertyId() );

		[ $revisionId, $lastModified ] = $this->getRevisionMetadata->execute( $propertyId );

		return new GetPropertyLabelResponse(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			$this->labelRetriever->getLabel( $propertyId, $request->getLanguageCode() ),
			$lastModified,
			$revisionId
		);
	}

}
