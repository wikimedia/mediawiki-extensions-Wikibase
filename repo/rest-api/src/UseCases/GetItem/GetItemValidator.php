<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItem;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\UseCases\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class GetItemValidator {
	public const SOURCE_ITEM_ID = 'item ID';
	public const SOURCE_FIELDS = 'fields';

	public function validate( GetItemRequest $request ): ?ValidationError {
		return $this->validateItemId( $request->getItemId() )
			?: $this->validateFields( $request->getFields() );
	}

	private function validateItemId( string $itemId ): ?ValidationError {
		try {
			// @phan-suppress-next-line PhanNoopNew
			new ItemId( $itemId );
		} catch ( InvalidArgumentException $ex ) {
			return new ValidationError( $itemId, self::SOURCE_ITEM_ID );
		}
		return null;
	}

	private function validateFields( array $fields ): ?ValidationError {
		foreach ( $fields as $field ) {
			if ( !in_array( $field, GetItemRequest::VALID_FIELDS ) ) {
				return new ValidationError( $field, self::SOURCE_FIELDS );
			}
		}
		return null;
	}
}
