<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItem;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Services\ItemDataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\ItemRedirectResponse;

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
	 * @return GetItemSuccessResponse|GetItemErrorResponse|ItemRedirectResponse
	 */
	public function execute( GetItemRequest $itemRequest ) {
		$validationError = $this->validator->validate( $itemRequest );

		if ( $validationError ) {
			return GetItemErrorResponse::newFromValidationError( $validationError );
		}

		$itemId = new ItemId( $itemRequest->getItemId() );
		$latestRevisionMetadata = $this->revisionMetadataRetriever->getLatestRevisionMetadata( $itemId );

		if ( !$latestRevisionMetadata->itemExists() ) {
			return new GetItemErrorResponse(
				ErrorResponse::ITEM_NOT_FOUND,
				"Could not find an item with the ID: {$itemRequest->getItemId()}"
			);
		} elseif ( $latestRevisionMetadata->isRedirect() ) {
			return new ItemRedirectResponse( $latestRevisionMetadata->getRedirectTarget()->getSerialization() );
		}

		return new GetItemSuccessResponse(
			$this->itemDataRetriever->getItemData( $itemId, $itemRequest->getFields() ),
			$latestRevisionMetadata->getRevisionTimestamp(),
			$latestRevisionMetadata->getRevisionId()
		);
	}

}
