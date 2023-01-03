<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemLabels;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Services\ItemLabelsRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;

/**
 * @license GPL-2.0-or-later
 */
class GetItemLabels {

	private ItemRevisionMetadataRetriever $itemRevisionMetadataRetriever;
	private ItemLabelsRetriever $itemLabelsRetriever;

	public function __construct(
		ItemRevisionMetadataRetriever $itemRevisionMetadataRetriever,
		ItemLabelsRetriever $itemLabelsRetriever
	) {
		$this->itemRevisionMetadataRetriever = $itemRevisionMetadataRetriever;
		$this->itemLabelsRetriever = $itemLabelsRetriever;
	}

	/**
	 * @return GetItemLabelsErrorResponse|GetItemLabelsSuccessResponse
	 */
	public function execute( GetItemLabelsRequest $request ) {
		$itemId = new ItemId( $request->getItemId() );
		$metaDataResult = $this->itemRevisionMetadataRetriever->getLatestRevisionMetadata( $itemId );

		if ( !$metaDataResult->itemExists() ) {
			return new GetItemLabelsErrorResponse(
				ErrorResponse::ITEM_NOT_FOUND,
				"Could not find an item with the ID: {$request->getItemId()}"
			);
		}

		return new GetItemLabelsSuccessResponse(
			$this->itemLabelsRetriever->getLabels( $itemId ),
			$metaDataResult->getRevisionTimestamp(),
			$metaDataResult->getRevisionId(),
		);
	}
}
