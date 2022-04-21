<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItem;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Filters\FieldFilter;
use Wikibase\Repo\RestApi\Domain\Serializers\ItemSerializer;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionRetriever;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;

/**
 * @license GPL-2.0-or-later
 */
class GetItem {

	private $itemRetriever;
	private $itemSerializer;
	private $validator;

	public function __construct(
		ItemRevisionRetriever $itemRetriever,
		ItemSerializer $itemSerializer,
		GetItemValidator $validator
	) {
		$this->itemRetriever = $itemRetriever;
		$this->itemSerializer = $itemSerializer;
		$this->validator = $validator;
	}

	/**
	 * @return GetItemSuccessResponse|GetItemErrorResponse|GetItemRedirectResponse
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
			return new GetItemRedirectResponse( $itemRevisionResult->getRedirectTarget()->getSerialization() );
		}

		$itemRevision = $itemRevisionResult->getRevision();
		$itemSerialization = $this->itemSerializer->serialize( $itemRevision->getItem() );
		$fields = $itemRequest->getFields();
		if ( $fields !== GetItemRequest::VALID_FIELDS ) {
			$itemSerialization = ( new FieldFilter( $fields ) )->filter( $itemSerialization );
		}

		return new GetItemSuccessResponse(
			$itemSerialization,
			$itemRevision->getLastModified(),
			$itemRevision->getRevisionId()
		);
	}
}
