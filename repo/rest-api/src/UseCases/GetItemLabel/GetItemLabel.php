<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemLabel;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Services\ItemLabelRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetItemLabel {

	private ItemRevisionMetadataRetriever $itemRevisionMetadataRetriever;
	private ItemLabelRetriever $itemLabelRetriever;

	public function __construct(
		ItemRevisionMetadataRetriever $itemRevisionMetadataRetriever,
		ItemLabelRetriever $itemLabelRetriever
	) {
		$this->itemRevisionMetadataRetriever = $itemRevisionMetadataRetriever;
		$this->itemLabelRetriever = $itemLabelRetriever;
	}

	public function execute( GetItemLabelRequest $request ): GetItemLabelResponse {
		$itemId = new ItemId( $request->getItemId() );

		$metaDataResult = $this->itemRevisionMetadataRetriever->getLatestRevisionMetadata( $itemId );

		return new GetItemLabelResponse(
			$this->itemLabelRetriever->getLabel( $itemId, $request->getLanguageCode() ),
			$metaDataResult->getRevisionTimestamp(),
			$metaDataResult->getRevisionId(),
		);
	}
}
