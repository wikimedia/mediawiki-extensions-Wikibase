<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemDescriptions;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Services\ItemDescriptionsRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\UseCases\UseCaseException;

/**
 * @license GPL-2.0-or-later
 */
class GetItemDescriptions {

	private ItemRevisionMetadataRetriever $itemRevisionMetadataRetriever;
	private ItemDescriptionsRetriever $itemDescriptionsRetriever;
	private GetItemDescriptionsValidator $validator;

	public function __construct(
		ItemRevisionMetadataRetriever $itemRevisionMetadataRetriever,
		ItemDescriptionsRetriever $itemDescriptionsRetriever,
		GetItemDescriptionsValidator $validator
	) {
		$this->itemRevisionMetadataRetriever = $itemRevisionMetadataRetriever;
		$this->itemDescriptionsRetriever = $itemDescriptionsRetriever;
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseException
	 */
	public function execute( GetItemDescriptionsRequest $request ): GetItemDescriptionsSuccessResponse {
		$this->validator->assertValidRequest( $request );

		$itemId = new ItemId( $request->getItemId() );

		$metaDataResult = $this->itemRevisionMetadataRetriever->getLatestRevisionMetadata( $itemId );

		return new GetItemDescriptionsSuccessResponse(
			$this->itemDescriptionsRetriever->getDescriptions( $itemId ),
			$metaDataResult->getRevisionTimestamp(),
			$metaDataResult->getRevisionId(),
		);
	}
}
