<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItem;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemData;

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
	 * @throws UseCaseError
	 */
	public function assertValidRequest( GetItemRequest $request ): void {
		$validationError = $this->itemIdValidator->validate( $request->getItemId() );

		if ( $validationError ) {
			throw new UseCaseError(
				UseCaseError::INVALID_ITEM_ID,
				'Not a valid item ID: ' . $validationError->getContext()[ItemIdValidator::CONTEXT_VALUE]
			);
		}

		$this->validateFields( $request->getFields() );
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateFields( array $fields ): void {
		foreach ( $fields as $field ) {
			if ( !in_array( $field, ItemData::VALID_FIELDS ) ) {
				throw new UseCaseError(
					UseCaseError::INVALID_FIELD,
					'Not a valid field: ' . $field
				);
			}
		}
	}
}
