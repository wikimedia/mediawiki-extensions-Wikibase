<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItem;

use Wikibase\Repo\RestApi\Domain\ReadModel\ItemData;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetItemValidator {

	public const CODE_INVALID_FIELD = 'invalid-field';

	private ItemIdValidator $itemIdValidator;

	public function __construct( ItemIdValidator $itemIdValidator ) {
		$this->itemIdValidator = $itemIdValidator;
	}

	/**
	 * @throws UseCaseException
	 */
	public function assertValidRequest( GetItemRequest $request ): void {
		$validationError = $this->itemIdValidator->validate( $request->getItemId() );

		if ( $validationError ) {
			throw new UseCaseException(
				ErrorResponse::INVALID_ITEM_ID,
				'Not a valid item ID: ' . $validationError->getContext()[ItemIdValidator::CONTEXT_VALUE]
			);
		}

		$this->validateFields( $request->getFields() );
	}

	/**
	 * @throws UseCaseException
	 */
	private function validateFields( array $fields ): void {
		foreach ( $fields as $field ) {
			if ( !in_array( $field, ItemData::VALID_FIELDS ) ) {
				throw new UseCaseException(
					ErrorResponse::INVALID_FIELD,
					'Not a valid field: ' . $field
				);
			}
		}
	}
}
