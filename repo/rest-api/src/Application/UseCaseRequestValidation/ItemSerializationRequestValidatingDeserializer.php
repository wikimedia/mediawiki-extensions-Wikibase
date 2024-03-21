<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCaseRequestValidation;

use LogicException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\ItemValidator;

/**
 * @license GPL-2.0-or-later
 */
class ItemSerializationRequestValidatingDeserializer {

	private ItemValidator $validator;

	public function __construct( ItemValidator $validator ) {
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( ItemSerializationRequest $request ): Item {
		$validationError = $this->validator->validate( $request->getItem() );

		if ( $validationError ) {
			$context = $validationError->getContext();
			switch ( $validationError->getCode() ) {
				case ItemValidator::CODE_INVALID_FIELD:
					throw new UseCaseError(
						UseCaseError::ITEM_DATA_INVALID_FIELD,
						"Invalid input for '{$context[ItemValidator::CONTEXT_FIELD_NAME]}'",
						[
							UseCaseError::CONTEXT_PATH => $context[ItemValidator::CONTEXT_FIELD_NAME],
							UseCaseError::CONTEXT_VALUE => $context[ItemValidator::CONTEXT_FIELD_VALUE],
						]
					);
				case ItemValidator::CODE_UNEXPECTED_FIELD:
					throw new UseCaseError(
						UseCaseError::ITEM_DATA_UNEXPECTED_FIELD,
						'The request body contains an unexpected field',
						[ UseCaseError::CONTEXT_FIELD => $context[ItemValidator::CONTEXT_FIELD_NAME] ]
					);
				case ItemValidator::CODE_MISSING_LABELS_AND_DESCRIPTIONS:
					throw new UseCaseError(
						UseCaseError::MISSING_LABELS_AND_DESCRIPTIONS,
						'Item requires at least a label or a description in a language'
					);
				default:
					throw new LogicException( "Unknown validation error code: {$validationError->getCode()}" );
			}
		}

		return $this->validator->getValidatedItem();
	}

}
