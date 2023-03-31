<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemLabel;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Services\ItemLabelRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetItemLabel {

	private ItemRevisionMetadataRetriever $itemRevisionMetadataRetriever;
	private ItemLabelRetriever $itemLabelRetriever;
	private GetItemLabelValidator $validator;

	public function __construct(
		ItemRevisionMetadataRetriever $itemRevisionMetadataRetriever,
		ItemLabelRetriever $itemLabelRetriever,
		GetItemLabelValidator $validator
	) {
		$this->itemRevisionMetadataRetriever = $itemRevisionMetadataRetriever;
		$this->itemLabelRetriever = $itemLabelRetriever;
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( GetItemLabelRequest $request ): GetItemLabelResponse {
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

		$label = $this->itemLabelRetriever->getLabel( $itemId, $request->getLanguageCode() );
		if ( !$label ) {
			throw new UseCaseError(
				UseCaseError::LABEL_NOT_DEFINED,
				"Item with the ID {$request->getItemId()} does not have a label in the language: {$request->getLanguageCode()}"
			);
		}

		return new GetItemLabelResponse(
			$label,
			$metaDataResult->getRevisionTimestamp(),
			$metaDataResult->getRevisionId(),
		);
	}
}
