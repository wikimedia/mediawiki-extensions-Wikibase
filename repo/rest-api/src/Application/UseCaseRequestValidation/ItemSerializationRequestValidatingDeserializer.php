<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCaseRequestValidation;

use LogicException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\ItemValidator;
use Wikibase\Repo\RestApi\Application\Validation\OldItemLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

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
			$this->handleLabelsValidationErrors( $validationError );
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
				case ItemValidator::CODE_INVALID_LANGUAGE_CODE:
					throw new UseCaseError(
						UseCaseError::INVALID_LANGUAGE_CODE,
						"Not a valid language code: {$context[ItemValidator::CONTEXT_FIELD_LANGUAGE]}",
						[
							UseCaseError::CONTEXT_PATH => $context[ItemValidator::CONTEXT_FIELD_NAME],
							UseCaseError::CONTEXT_LANGUAGE => $context[ItemValidator::CONTEXT_FIELD_LANGUAGE],
						]
					);
				case ItemValidator::CODE_MISSING_LABELS_AND_DESCRIPTIONS:
					throw new UseCaseError(
						UseCaseError::MISSING_LABELS_AND_DESCRIPTIONS,
						'Item requires at least a label or a description in a language'
					);
				case ItemValidator::CODE_LABEL_DESCRIPTION_SAME_VALUE:
					throw new UseCaseError(
						UseCaseError::LABEL_DESCRIPTION_SAME_VALUE,
						"Label and description for language '{$context[ItemValidator::CONTEXT_FIELD_LANGUAGE]}'" .
						' can not have the same value',
						[ UseCaseError::CONTEXT_LANGUAGE => $context[ItemValidator::CONTEXT_FIELD_LANGUAGE] ]
					);
				case ItemValidator::CODE_LABEL_DESCRIPTION_DUPLICATE:
					throw new UseCaseError(
						UseCaseError::ITEM_LABEL_DESCRIPTION_DUPLICATE,
						"Item '{$context[ItemValidator::CONTEXT_MATCHING_ITEM_ID]}' already has label " .
						"'{$context[ItemValidator::CONTEXT_FIELD_LABEL]}' associated with language code " .
						"'{$context[ItemValidator::CONTEXT_FIELD_LANGUAGE]}', using the same description text",
						[
							UseCaseError::CONTEXT_LANGUAGE => $context[ItemValidator::CONTEXT_FIELD_LANGUAGE],
							UseCaseError::CONTEXT_LABEL => $context[ItemValidator::CONTEXT_FIELD_LABEL],
							UseCaseError::CONTEXT_DESCRIPTION => $context[ItemValidator::CONTEXT_FIELD_DESCRIPTION],
							UseCaseError::CONTEXT_MATCHING_ITEM_ID => $context[ItemValidator::CONTEXT_MATCHING_ITEM_ID],
						]
					);
				default:
					throw new LogicException( "Unknown validation error code: {$validationError->getCode()}" );
			}
		}

		return $this->validator->getValidatedItem();
	}

	private function handleLabelsValidationErrors( ValidationError $validationError ): void {
		$context = $validationError->getContext();
		switch ( $validationError->getCode() ) {
			case OldItemLabelValidator::CODE_EMPTY:
				throw new UseCaseError(
					UseCaseError::LABEL_EMPTY,
					'Label must not be empty',
					[ UseCaseError::CONTEXT_LANGUAGE => $context[OldItemLabelValidator::CONTEXT_LANGUAGE] ]
				);
			case OldItemLabelValidator::CODE_INVALID:
				throw new UseCaseError(
					UseCaseError::INVALID_LABEL,
					"Not a valid label: {$context[OldItemLabelValidator::CONTEXT_LABEL]}",
					[ UseCaseError::CONTEXT_LANGUAGE => $context[OldItemLabelValidator::CONTEXT_LANGUAGE] ]
				);
			case OldItemLabelValidator::CODE_TOO_LONG:
				throw new UseCaseError(
					UseCaseError::LABEL_TOO_LONG,
					"Label must be no more than {$context[OldItemLabelValidator::CONTEXT_LIMIT]} characters long",
					[
						UseCaseError::CONTEXT_LANGUAGE => $context[OldItemLabelValidator::CONTEXT_LANGUAGE],
						UseCaseError::CONTEXT_CHARACTER_LIMIT => $context[OldItemLabelValidator::CONTEXT_LIMIT],
					]
				);
		}
	}

}
