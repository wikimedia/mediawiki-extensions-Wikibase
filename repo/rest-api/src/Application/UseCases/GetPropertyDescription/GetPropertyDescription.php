<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyDescription;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestPropertyRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Services\PropertyDescriptionRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyDescription {

	private GetLatestPropertyRevisionMetadata $getRevisionMetadata;
	private PropertyDescriptionRetriever $descriptionRetriever;

	public function __construct(
		GetLatestPropertyRevisionMetadata $getRevisionMetadata,
		PropertyDescriptionRetriever $descriptionsRetriever
	) {
		$this->getRevisionMetadata = $getRevisionMetadata;
		$this->descriptionRetriever = $descriptionsRetriever;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( GetPropertyDescriptionRequest $request ): GetPropertyDescriptionResponse {
		$propertyId = new NumericPropertyId( $request->getPropertyId() );

		[ $revisionId, $lastModified ] = $this->getRevisionMetadata->execute( $propertyId );

		return new GetPropertyDescriptionResponse(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			$this->descriptionRetriever->getDescription( $propertyId, $request->getLanguageCode() ),
			$lastModified,
			$revisionId
		);
	}
}
