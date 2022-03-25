<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItem;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemValidationResult as Result;
use Wikibase\Repo\RestApi\UseCases\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class GetItemValidator {
	public function validate( GetItemRequest $request ): Result {
		$itemIdError = $this->validateItemId( $request->getItemId() );
		if ( $itemIdError ) {
			return new Result( $itemIdError );
		}

		$fieldsError = $this->validateFields( $request->getFields() );
		if ( $fieldsError ) {
			return new Result( $fieldsError );
		}

		return new Result();
	}

	private function validateItemId( string $itemId ): ?ValidationError {
		try {
			// @phan-suppress-next-line PhanNoopNew
			new ItemId( $itemId );
		} catch ( InvalidArgumentException $ex ) {
			return new ValidationError( $itemId, Result::SOURCE_ITEM_ID );
		}
		return null;
	}

	private function validateFields( ?array $fields ): ?ValidationError {
		if ( $fields === null ) {
			return null;
		}
		foreach ( $fields as $field ) {
			if ( !in_array( $field, GetItemRequest::VALID_FIELDS ) ) {
				return new ValidationError( $field, Result::SOURCE_FIELDS );
			}
		}
		return null;
	}
}
