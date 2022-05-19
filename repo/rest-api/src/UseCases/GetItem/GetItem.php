<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItem;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Model\ItemData;
use Wikibase\Repo\RestApi\Domain\Model\ItemDataBuilder;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionRetriever;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\ItemRedirectResponse;

/**
 * @license GPL-2.0-or-later
 */
class GetItem {

	private $itemRetriever;
	private $validator;

	public function __construct(
		ItemRevisionRetriever $itemRetriever,
		GetItemValidator $validator
	) {
		$this->itemRetriever = $itemRetriever;
		$this->validator = $validator;
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
		$itemRevisionResult = $this->itemRetriever->getItemRevision( $itemId );

		if ( !$itemRevisionResult->itemExists() ) {
			return new GetItemErrorResponse(
				ErrorResponse::ITEM_NOT_FOUND,
				"Could not find an item with the ID: {$itemRequest->getItemId()}"
			);
		} elseif ( $itemRevisionResult->isRedirect() ) {
			return new ItemRedirectResponse( $itemRevisionResult->getRedirectTarget()->getSerialization() );
		}
		$itemRevision = $itemRevisionResult->getRevision();

		return new GetItemSuccessResponse(
			$this->itemDataFromFields( $itemRequest->getFields(), $itemRevision->getItem() ),
			$itemRevision->getLastModified(),
			$itemRevision->getRevisionId()
		);
	}

	/**
	 * This looks out of place here and is intentionally left untested in the use case unit test for now.
	 * It will move into the ItemDataRetriever service as part of T307915.
	 */
	private function itemDataFromFields( array $fields, Item $item ): ItemData {
		$itemData = ( new ItemDataBuilder() )->setId( $item->getId() );

		if ( in_array( GetItemRequest::FIELD_TYPE, $fields ) ) {
			$itemData->setType( $item->getType() );
		}
		if ( in_array( GetItemRequest::FIELD_LABELS, $fields ) ) {
			$itemData->setLabels( $item->getLabels() );
		}
		if ( in_array( GetItemRequest::FIELD_DESCRIPTIONS, $fields ) ) {
			$itemData->setDescriptions( $item->getDescriptions() );
		}
		if ( in_array( GetItemRequest::FIELD_ALIASES, $fields ) ) {
			$itemData->setAliases( $item->getAliasGroups() );
		}
		if ( in_array( GetItemRequest::FIELD_STATEMENTS, $fields ) ) {
			$itemData->setStatements( $item->getStatements() );
		}
		if ( in_array( GetItemRequest::FIELD_SITELINKS, $fields ) ) {
			$itemData->setSiteLinks( $item->getSiteLinkList() );
		}

		return $itemData->build();
	}
}
