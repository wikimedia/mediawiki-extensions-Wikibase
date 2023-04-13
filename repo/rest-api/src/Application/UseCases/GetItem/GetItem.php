<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItem;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Services\ItemDataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;

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
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable Item validated and exists
			$this->itemDataRetriever->getItemData( $itemId, $itemRequest->getFields() ),
			$latestRevisionMetadata->getRevisionTimestamp(),
			$latestRevisionMetadata->getRevisionId()
		);
	}

}
