<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRevisionMetadataRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetLatestPropertyRevisionMetadata {

	private PropertyRevisionMetadataRetriever $metadataRetriever;

	public function __construct( PropertyRevisionMetadataRetriever $metadataRetriever ) {
		$this->metadataRetriever = $metadataRetriever;
	}

	/**
	 * @throws UseCaseError if the property does not exist
	 *
	 * @return array{int, string}
	 */
	public function execute( NumericPropertyId $id ): array {
		$metaDataResult = $this->metadataRetriever->getLatestRevisionMetadata( $id );

		if ( !$metaDataResult->propertyExists() ) {
			throw new UseCaseError(
				UseCaseError::PROPERTY_NOT_FOUND,
				"Could not find a property with the ID: {$id}"
			);
		}

		return [ $metaDataResult->getRevisionId(), $metaDataResult->getRevisionTimestamp() ];
	}

}
