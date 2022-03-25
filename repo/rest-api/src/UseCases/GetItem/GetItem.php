<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItem;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Filters\FieldFilter;
use Wikibase\Repo\RestApi\Domain\Serializers\ItemSerializer;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionRetriever;
use Wikibase\Repo\RestApi\UseCases\ErrorResult;

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
	 * @return GetItemSuccessResult|GetItemErrorResult
	 */
	public function execute( GetItemRequest $itemRequest ) {
		$validationResult = $this->validator->validate( $itemRequest );

		if ( $validationResult->hasError() ) {
			return GetItemErrorResult::newFromValidationError(
				$validationResult->getError()
			);
		}

		try {
			$itemId = new ItemId( $itemRequest->getItemId() );
			$itemRevision = $this->itemRetriever->getItemRevision( $itemId );
		} catch ( \Exception $e ) {
			return new GetItemErrorResult( ErrorResult::UNEXPECTED_ERROR, "Unexpected Error" );
		}

		if ( $itemRevision === null ) {
			return new GetItemErrorResult(
				ErrorResult::ITEM_NOT_FOUND,
				"Could not find an item with the ID {$itemRequest->getItemId()}"
			);
		}

		$itemSerialization = $this->itemSerializer->serialize( $itemRevision->getItem() );
		$fields = $itemRequest->getFields();
		if ( $fields !== null ) {
			$itemSerialization = ( new FieldFilter( $fields ) )->filter( $itemSerialization );
		}

		return new GetItemSuccessResult(
			$itemSerialization,
			$itemRevision->getLastModified(),
			$itemRevision->getRevisionId()
		);
	}
}
