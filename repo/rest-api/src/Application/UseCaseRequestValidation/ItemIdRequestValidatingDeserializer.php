<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCaseRequestValidation;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;

/**
 * @license GPL-2.0-or-later
 */
class ItemIdRequestValidatingDeserializer {
	private ItemIdValidator $itemIdValidator;

	public function __construct( ItemIdValidator $validator ) {
		$this->itemIdValidator = $validator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( ItemIdRequest $request ): ItemId {
		$validationError = $this->itemIdValidator->validate( $request->getItemId() );
		if ( $validationError ) {
			throw new UseCaseError(
				UseCaseError::INVALID_ITEM_ID,
				"Not a valid item ID: {$validationError->getContext()[ItemIdValidator::CONTEXT_VALUE]}"
			);
		}
		return new ItemId( $request->getItemId() );
	}

}
