<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItem;

use Wikibase\Repo\RestApi\Domain\Model\ItemData;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class GetItemValidator {
	public const SOURCE_ITEM_ID = 'item ID';
	public const SOURCE_FIELDS = 'fields';

	public const ERROR_CONTEXT_FIELD_VALUE = 'field-value';

	private ItemIdValidator $itemIdValidator;

	public function __construct( ItemIdValidator $itemIdValidator ) {
		$this->itemIdValidator = $itemIdValidator;
	}

	public function validate( GetItemRequest $request ): ?ValidationError {
		return $this->itemIdValidator->validate( $request->getItemId(), self::SOURCE_ITEM_ID )
			?: $this->validateFields( $request->getFields() );
	}

	private function validateFields( array $fields ): ?ValidationError {
		foreach ( $fields as $field ) {
			if ( !in_array( $field, ItemData::VALID_FIELDS ) ) {
				return new ValidationError( self::SOURCE_FIELDS, [ self::ERROR_CONTEXT_FIELD_VALUE => $field ] );
			}
		}
		return null;
	}
}
