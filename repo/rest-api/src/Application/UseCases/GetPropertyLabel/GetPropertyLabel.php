<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabel;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestPropertyRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
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

	/**
	 * @throws UseCaseError
	 */
	public function execute( GetPropertyLabelRequest $request ): GetPropertyLabelResponse {
		$propertyId = new NumericPropertyId( $request->getPropertyId() );
		$languageCode = $request->getLanguageCode();

		[ $revisionId, $lastModified ] = $this->getRevisionMetadata->execute( $propertyId );

		$label = $this->labelRetriever->getLabel( $propertyId, $languageCode );
		if ( !$label ) {
			throw new UseCaseError(
				UseCaseError::LABEL_NOT_DEFINED,
				"Property with the ID {$propertyId->getSerialization()} does not have a label in the language: {$languageCode}"
			);
		}

		return new GetPropertyLabelResponse( $label, $lastModified, $revisionId );
	}

}
