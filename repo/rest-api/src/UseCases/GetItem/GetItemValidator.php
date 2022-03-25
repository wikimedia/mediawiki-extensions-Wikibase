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
		$result = new Result();

		$itemIdError = $this->validateItemId( $request->getItemId() );
		if ( $itemIdError ) {
			$result->setError( $itemIdError );
		}

		return $result;
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
}
