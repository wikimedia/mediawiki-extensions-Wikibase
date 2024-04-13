<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCaseRequestValidation;

use LogicException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\AliasesInLanguageValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemAliasesValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemDescriptionValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelsAndDescriptionsValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
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
			$this->handleLabelValidationErrors( $validationError );
			$this->handleDescriptionValidationErrors( $validationError );
			$this->handleAliasesValidationErrors( $validationError );
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
				case ItemLabelsAndDescriptionsValidator::CODE_INVALID_FIELD:
					throw new UseCaseError(
						UseCaseError::ITEM_DATA_INVALID_FIELD,
						"Invalid input for '{$context[ItemLabelsAndDescriptionsValidator::CONTEXT_FIELD_NAME]}'",
						[
							UseCaseError::CONTEXT_PATH => $context[ItemLabelsAndDescriptionsValidator::CONTEXT_FIELD_NAME],
							UseCaseError::CONTEXT_VALUE => $context[ItemLabelsAndDescriptionsValidator::CONTEXT_FIELD_VALUE],
						]
					);
				case ItemAliasesValidator::CODE_INVALID_ALIASES:
					throw new UseCaseError(
						UseCaseError::ITEM_DATA_INVALID_FIELD,
						"Invalid input for 'aliases'",
						[
							UseCaseError::CONTEXT_PATH => 'aliases',
							UseCaseError::CONTEXT_VALUE => $context[ItemAliasesValidator::CONTEXT_FIELD_ALIASES],
						]
					);
				case ItemValidator::CODE_UNEXPECTED_FIELD:
					throw new UseCaseError(
						UseCaseError::ITEM_DATA_UNEXPECTED_FIELD,
						'The request body contains an unexpected field',
						[ UseCaseError::CONTEXT_FIELD => $context[ItemValidator::CONTEXT_FIELD_NAME] ]
					);
				case LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE:
					throw new UseCaseError(
						UseCaseError::INVALID_LANGUAGE_CODE,
						"Not a valid language code: {$context[LanguageCodeValidator::CONTEXT_LANGUAGE_CODE_VALUE]}",
						[
							UseCaseError::CONTEXT_PATH => $context[LanguageCodeValidator::CONTEXT_PATH_VALUE],
							UseCaseError::CONTEXT_LANGUAGE => $context[LanguageCodeValidator::CONTEXT_LANGUAGE_CODE_VALUE],
						]
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

	private function handleLabelValidationErrors( ValidationError $validationError ): void {
		$context = $validationError->getContext();
		switch ( $validationError->getCode() ) {
			case ItemLabelValidator::CODE_EMPTY:
				throw new UseCaseError(
					UseCaseError::LABEL_EMPTY,
					'Label must not be empty',
					[ UseCaseError::CONTEXT_LANGUAGE => $context[ItemLabelValidator::CONTEXT_LANGUAGE] ]
				);
			case ItemLabelValidator::CODE_INVALID:
				throw new UseCaseError(
					UseCaseError::INVALID_LABEL,
					"Not a valid label: {$context[ItemLabelValidator::CONTEXT_LABEL]}",
					[ UseCaseError::CONTEXT_LANGUAGE => $context[ItemLabelValidator::CONTEXT_LANGUAGE] ]
				);
			case ItemLabelValidator::CODE_TOO_LONG:
				throw new UseCaseError(
					UseCaseError::LABEL_TOO_LONG,
					"Label must be no more than {$context[ItemLabelValidator::CONTEXT_LIMIT]} characters long",
					[
						UseCaseError::CONTEXT_LANGUAGE => $context[ItemLabelValidator::CONTEXT_LANGUAGE],
						UseCaseError::CONTEXT_CHARACTER_LIMIT => $context[ItemLabelValidator::CONTEXT_LIMIT],
					]
				);
			case ItemLabelValidator::CODE_LABEL_SAME_AS_DESCRIPTION:
				throw new UseCaseError(
					UseCaseError::LABEL_DESCRIPTION_SAME_VALUE,
					"Label and description for language '{$context[ItemLabelValidator::CONTEXT_LANGUAGE]}'" .
					' can not have the same value',
					[ UseCaseError::CONTEXT_LANGUAGE => $context[ItemLabelValidator::CONTEXT_LANGUAGE] ]
				);
			case ItemLabelValidator::CODE_LABEL_DESCRIPTION_DUPLICATE:
				throw new UseCaseError(
					UseCaseError::ITEM_LABEL_DESCRIPTION_DUPLICATE,
					"Item '{$context[ItemLabelValidator::CONTEXT_MATCHING_ITEM_ID]}' already has label " .
					"'{$context[ItemLabelValidator::CONTEXT_LABEL]}' associated with language code " .
					"'{$context[ItemLabelValidator::CONTEXT_LANGUAGE]}', using the same description text",
					[
						UseCaseError::CONTEXT_LANGUAGE => $context[ItemLabelValidator::CONTEXT_LANGUAGE],
						UseCaseError::CONTEXT_LABEL => $context[ItemLabelValidator::CONTEXT_LABEL],
						UseCaseError::CONTEXT_DESCRIPTION => $context[ItemLabelValidator::CONTEXT_DESCRIPTION],
						UseCaseError::CONTEXT_MATCHING_ITEM_ID => $context[ItemLabelValidator::CONTEXT_MATCHING_ITEM_ID],
					]
				);
		}
	}

	private function handleDescriptionValidationErrors( ValidationError $validationError ): void {
		$context = $validationError->getContext();
		switch ( $validationError->getCode() ) {
			case ItemDescriptionValidator::CODE_EMPTY:
				throw new UseCaseError(
					UseCaseError::DESCRIPTION_EMPTY,
					'Description must not be empty',
					[ UseCaseError::CONTEXT_LANGUAGE => $context[ItemDescriptionValidator::CONTEXT_LANGUAGE] ]
				);
			case ItemDescriptionValidator::CODE_INVALID:
				throw new UseCaseError(
					UseCaseError::INVALID_DESCRIPTION,
					"Not a valid description: {$context[ItemDescriptionValidator::CONTEXT_DESCRIPTION]}",
					[ UseCaseError::CONTEXT_LANGUAGE => $context[ItemDescriptionValidator::CONTEXT_LANGUAGE] ]
				);
			case ItemDescriptionValidator::CODE_TOO_LONG:
				throw new UseCaseError(
					UseCaseError::DESCRIPTION_TOO_LONG,
					"Description must be no more than {$context[ItemDescriptionValidator::CONTEXT_LIMIT]} characters long",
					[
						UseCaseError::CONTEXT_LANGUAGE => $context[ItemDescriptionValidator::CONTEXT_LANGUAGE],
						UseCaseError::CONTEXT_CHARACTER_LIMIT => $context[ItemDescriptionValidator::CONTEXT_LIMIT],
					]
				);
			case ItemDescriptionValidator::CODE_DESCRIPTION_SAME_AS_LABEL:
				throw new UseCaseError(
					UseCaseError::LABEL_DESCRIPTION_SAME_VALUE,
					"Label and description for language '{$context[ItemDescriptionValidator::CONTEXT_LANGUAGE]}'" .
					' can not have the same value',
					[ UseCaseError::CONTEXT_LANGUAGE => $context[ItemDescriptionValidator::CONTEXT_LANGUAGE] ]
				);
			case ItemDescriptionValidator::CODE_DESCRIPTION_LABEL_DUPLICATE:
				throw new UseCaseError(
					UseCaseError::ITEM_LABEL_DESCRIPTION_DUPLICATE,
					"Item '{$context[ItemDescriptionValidator::CONTEXT_MATCHING_ITEM_ID]}' already has label " .
					"'{$context[ItemDescriptionValidator::CONTEXT_LABEL]}' associated with language code " .
					"'{$context[ItemDescriptionValidator::CONTEXT_LANGUAGE]}', using the same description text",
					[
						UseCaseError::CONTEXT_LANGUAGE => $context[ItemDescriptionValidator::CONTEXT_LANGUAGE],
						UseCaseError::CONTEXT_LABEL => $context[ItemDescriptionValidator::CONTEXT_LABEL],
						UseCaseError::CONTEXT_DESCRIPTION => $context[ItemDescriptionValidator::CONTEXT_DESCRIPTION],
						UseCaseError::CONTEXT_MATCHING_ITEM_ID => $context[ItemDescriptionValidator::CONTEXT_MATCHING_ITEM_ID],
					]
				);
		}
	}

	private function handleAliasesValidationErrors( ValidationError $validationError ): void {
		$context = $validationError->getContext();
		switch ( $validationError->getCode() ) {
			case ItemAliasesValidator::CODE_EMPTY_ALIAS:
				throw new UseCaseError(
					UseCaseError::ALIAS_EMPTY,
					'Alias must not be empty',
					[ UseCaseError::CONTEXT_LANGUAGE => $context[ItemAliasesValidator::CONTEXT_FIELD_LANGUAGE] ]
				);
			case ItemAliasesValidator::CODE_EMPTY_ALIAS_LIST:
				throw new UseCaseError(
					UseCaseError::ALIAS_LIST_EMPTY,
					'Alias list must not be empty',
					[ UseCaseError::CONTEXT_LANGUAGE => $context[ItemAliasesValidator::CONTEXT_FIELD_LANGUAGE] ]
				);
			case ItemAliasesValidator::CODE_DUPLICATE_ALIAS:
				throw new UseCaseError(
					UseCaseError::ALIAS_DUPLICATE,
					"Alias list contains a duplicate alias: '{$context[ItemAliasesValidator::CONTEXT_FIELD_ALIAS]}'",
					[
						UseCaseError::CONTEXT_LANGUAGE => $context[ItemAliasesValidator::CONTEXT_FIELD_LANGUAGE],
						UseCaseError::CONTEXT_ALIAS => $context[ItemAliasesValidator::CONTEXT_FIELD_ALIAS],
					]
				);
			case ItemAliasesValidator::CODE_TOO_LONG_ALIAS:
				throw new UseCaseError(
					UseCaseError::ALIAS_TOO_LONG,
					"Alias must be no more than {$context[ItemAliasesValidator::CONTEXT_FIELD_LIMIT]} characters long",
					[
						UseCaseError::CONTEXT_LANGUAGE => $context[ItemAliasesValidator::CONTEXT_FIELD_LANGUAGE],
						UseCaseError::CONTEXT_CHARACTER_LIMIT => $context[ItemAliasesValidator::CONTEXT_FIELD_LIMIT],
					]
				);
			case ItemAliasesValidator::CODE_INVALID_ALIAS_LIST:
				$language = $context[ItemAliasesValidator::CONTEXT_FIELD_LANGUAGE];
				throw new UseCaseError(
					UseCaseError::INVALID_ALIAS_LIST,
					'Not a valid alias list',
					[ UseCaseError::CONTEXT_LANGUAGE => $language ]
				);
			case ItemAliasesValidator::CODE_INVALID_ALIAS:
				$aliasValue = $context[ItemAliasesValidator::CONTEXT_FIELD_ALIAS] ?? $context[AliasesInLanguageValidator::CONTEXT_VALUE];
				throw new UseCaseError(
					UseCaseError::INVALID_ALIAS,
					"Not a valid alias: $aliasValue",
					[ UseCaseError::CONTEXT_LANGUAGE => $context[ItemAliasesValidator::CONTEXT_FIELD_LANGUAGE] ]
				);
		}
	}

}
