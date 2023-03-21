<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemDescription;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Services\ItemDescriptionRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class GetItemDescription {

	private ItemRevisionMetadataRetriever $itemRevisionMetadataRetriever;
	private ItemDescriptionRetriever $itemDescriptionRetriever;
	private GetItemDescriptionValidator $validator;

	public function __construct(
		ItemRevisionMetadataRetriever $itemRevisionMetadataRetriever,
		ItemDescriptionRetriever $itemDescriptionRetriever,
		GetItemDescriptionValidator $validator
	) {
		$this->itemRevisionMetadataRetriever = $itemRevisionMetadataRetriever;
		$this->itemDescriptionRetriever = $itemDescriptionRetriever;
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseError
	 * @throws ItemRedirect
	 */
	public function execute( GetItemDescriptionRequest $request ): GetItemDescriptionResponse {
		$this->validator->assertValidRequest( $request );

		$itemId = new ItemId( $request->getItemId() );

		$metaDataResult = $this->itemRevisionMetadataRetriever->getLatestRevisionMetadata( $itemId );

		if ( !$metaDataResult->itemExists() ) {
			throw new UseCaseError(
				UseCaseError::ITEM_NOT_FOUND,
				"Could not find an item with the ID: {$request->getItemId()}"
			);
		}
		if ( $metaDataResult->isRedirect() ) {
			throw new ItemRedirect(
				$metaDataResult->getRedirectTarget()->getSerialization()
			);
		}

		$description = $this->itemDescriptionRetriever->getDescription( $itemId, $request->getLanguageCode() );
		if ( $description === null ) {
			throw new UseCaseError(
				UseCaseError::DESCRIPTION_NOT_DEFINED,
				"Item with the ID {$itemId} does not have a description in the language: {$request->getLanguageCode()}"
			);
		}

		return new GetItemDescriptionResponse(
			$description,
			$metaDataResult->getRevisionTimestamp(),
			$metaDataResult->getRevisionId(),
		);
	}
}
