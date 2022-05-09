<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItem;

use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class GetItemValidator {
	public const SOURCE_ITEM_ID = 'item ID';
	public const SOURCE_FIELDS = 'fields';

	private $itemIdValidator;

	public function __construct( ItemIdValidator $itemIdValidator ) {
		$this->itemIdValidator = $itemIdValidator;
	}

	public function validate( GetItemRequest $request ): ?ValidationError {
		return $this->itemIdValidator->validate( $request->getItemId(), self::SOURCE_ITEM_ID )
			?: $this->validateFields( $request->getFields() );
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
