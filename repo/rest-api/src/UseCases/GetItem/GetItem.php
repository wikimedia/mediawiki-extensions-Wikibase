<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItem;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Services\ItemDataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class GetItem {

	private ItemRevisionMetadataRetriever $revisionMetadataRetriever;
	private ItemDataRetriever $itemDataRetriever;
	private GetItemValidator $validator;

	public function __construct(
		ItemRevisionMetadataRetriever $revisionMetadataRetriever,
		ItemDataRetriever $itemDataRetriever,
		GetItemValidator $validator
	) {
		$this->validator = $validator;
		$this->revisionMetadataRetriever = $revisionMetadataRetriever;
		$this->itemDataRetriever = $itemDataRetriever;
	}

	/**
	 * @throws UseCaseError
	 * @throws ItemRedirect
	 */
	public function execute( GetItemRequest $itemRequest ): GetItemResponse {
		$this->validator->assertValidRequest( $itemRequest );

		$itemId = new ItemId( $itemRequest->getItemId() );
		$latestRevisionMetadata = $this->revisionMetadataRetriever->getLatestRevisionMetadata( $itemId );

		if ( !$latestRevisionMetadata->itemExists() ) {
			throw new UseCaseError(
				UseCaseError::ITEM_NOT_FOUND,
				"Could not find an item with the ID: {$itemRequest->getItemId()}"
			);
		}

		if ( $latestRevisionMetadata->isRedirect() ) {
			throw new ItemRedirect(
				$latestRevisionMetadata->getRedirectTarget()->getSerialization()
			);
		}

		return new GetItemResponse(
			$this->itemDataRetriever->getItemData( $itemId, $itemRequest->getFields() ),
			$latestRevisionMetadata->getRevisionTimestamp(),
			$latestRevisionMetadata->getRevisionId()
		);
	}

}
