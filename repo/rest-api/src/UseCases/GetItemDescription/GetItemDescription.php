<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemDescription;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Services\ItemDescriptionRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;

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

	public function execute( GetItemDescriptionRequest $request ): GetItemDescriptionResponse {
		$this->validator->assertValidRequest( $request );

		$itemId = new ItemId( $request->getItemId() );

		$metaDataResult = $this->itemRevisionMetadataRetriever->getLatestRevisionMetadata( $itemId );

		return new GetItemDescriptionResponse(
			$this->itemDescriptionRetriever->getDescription( $itemId, $request->getLanguageCode() ),
			$metaDataResult->getRevisionTimestamp(),
			$metaDataResult->getRevisionId(),
		);
	}
}
