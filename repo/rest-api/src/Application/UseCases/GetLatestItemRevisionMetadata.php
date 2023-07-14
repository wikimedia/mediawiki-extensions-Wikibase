<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetLatestItemRevisionMetadata {

	private ItemRevisionMetadataRetriever $metadataRetriever;

	public function __construct( ItemRevisionMetadataRetriever $metadataRetriever ) {
		$this->metadataRetriever = $metadataRetriever;
	}

	/**
	 * @throws ItemRedirect if the item is a redirect
	 * @throws UseCaseError if the item does not exist
	 *
	 * @return array{int, string}
	 */
	public function execute( ItemId $id ): array {
		$metaDataResult = $this->metadataRetriever->getLatestRevisionMetadata( $id );

		if ( !$metaDataResult->itemExists() ) {
			throw new UseCaseError( UseCaseError::ITEM_NOT_FOUND, "Could not find an item with the ID: $id" );
		}

		if ( $metaDataResult->isRedirect() ) {
			throw new ItemRedirect( $metaDataResult->getRedirectTarget()->getSerialization() );
		}

		return [ $metaDataResult->getRevisionId(), $metaDataResult->getRevisionTimestamp() ];
	}

}
