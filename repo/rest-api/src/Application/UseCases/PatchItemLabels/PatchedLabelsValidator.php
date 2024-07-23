<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels;

use LogicException;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelsContentsValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\LabelsSyntaxValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class PatchedLabelsValidator {

	private LabelsSyntaxValidator $syntaxValidator;
	private ItemLabelsContentsValidator $contentsValidator;

	public function __construct( LabelsSyntaxValidator $syntaxValidator, ItemLabelsContentsValidator $contentsValidator ) {
		$this->syntaxValidator = $syntaxValidator;
		$this->contentsValidator = $contentsValidator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize(
		TermList $originalLabels,
		TermList $originalDescriptions,
		array $labelsSerialization
	): TermList {
		$error = $this->syntaxValidator->validate( $labelsSerialization ) ?:
			$this->contentsValidator->validate(
				$this->syntaxValidator->getPartiallyValidatedLabels(),
				$originalDescriptions,
				$this->getModifiedLanguages( $originalLabels, $this->syntaxValidator->getPartiallyValidatedLabels() )
			);

		if ( $error ) {
			$this->throwUseCaseError( $error );
		}

		return $this->contentsValidator->getValidatedLabels();
	}

	private function getModifiedLanguages( TermList $original, TermList $modified ): array {
		return array_keys( array_filter(
			iterator_to_array( $modified ),
			fn( Term $label ) => !$original->hasTermForLanguage( $label->getLanguageCode() ) ||
				!$original->getByLanguage( $label->getLanguageCode() )->equals( $label )
		) );
	}

	/**
	 * @return never
	 */
	private function throwUseCaseError( ValidationError $validationError ): void {
		$context = $validationError->getContext();
		switch ( $validationError->getCode() ) {
			case LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE:
				$languageCode = $validationError->getContext()[LanguageCodeValidator::CONTEXT_LANGUAGE_CODE];
				throw new UseCaseError(
					UseCaseError::PATCHED_LABEL_INVALID_LANGUAGE_CODE,
					"Not a valid language code '$languageCode' in changed labels",
					[ UseCaseError::CONTEXT_LANGUAGE => $languageCode ]
				);
			case LabelsSyntaxValidator::CODE_EMPTY_LABEL:
				$languageCode = $validationError->getContext()[LabelsSyntaxValidator::CONTEXT_LANGUAGE];
				throw new UseCaseError(
					UseCaseError::PATCHED_LABEL_EMPTY,
					"Changed label for '$languageCode' cannot be empty",
					[ UseCaseError::CONTEXT_LANGUAGE => $languageCode ]
				);
			case LabelsSyntaxValidator::CODE_INVALID_LABEL_TYPE:
				$language = $context[LabelsSyntaxValidator::CONTEXT_LANGUAGE];
				$value = json_encode( $context[LabelsSyntaxValidator::CONTEXT_LABEL] );
				throw new UseCaseError(
					UseCaseError::PATCHED_LABEL_INVALID,
					"Changed label for '{$language}' is invalid: {$value}",
					[ UseCaseError::CONTEXT_LANGUAGE => $language, UseCaseError::CONTEXT_VALUE => $value ]
				);
			case ItemLabelValidator::CODE_INVALID:
				$language = $context[ItemLabelValidator::CONTEXT_LANGUAGE];
				$value = $context[ItemLabelValidator::CONTEXT_LABEL];
				throw new UseCaseError(
					UseCaseError::PATCHED_LABEL_INVALID,
					"Changed label for '{$language}' is invalid: {$value}",
					[ UseCaseError::CONTEXT_LANGUAGE => $language, UseCaseError::CONTEXT_VALUE => $value ]
				);
			case ItemLabelValidator::CODE_TOO_LONG:
				$maxLabelLength = $context[ItemLabelValidator::CONTEXT_LIMIT];
				$language = $context[ItemLabelValidator::CONTEXT_LANGUAGE];
				throw UseCaseError::newValueTooLong( "/$language", $maxLabelLength, true );
			case ItemLabelValidator::CODE_LABEL_DESCRIPTION_DUPLICATE:
				$languageCode = $context[ItemLabelValidator::CONTEXT_LANGUAGE];
				$label = $context[ItemLabelValidator::CONTEXT_LABEL];
				$duplicateItemId = $context[ItemLabelValidator::CONTEXT_MATCHING_ITEM_ID];
				throw new UseCaseError(
					UseCaseError::PATCHED_ITEM_LABEL_DESCRIPTION_DUPLICATE,
					"Item $duplicateItemId already has label '$label' associated with language " .
					"code $languageCode, using the same description text.",
					[
						UseCaseError::CONTEXT_LANGUAGE => $languageCode,
						UseCaseError::CONTEXT_LABEL => $label,
						UseCaseError::CONTEXT_DESCRIPTION => $context[ItemLabelValidator::CONTEXT_DESCRIPTION],
						UseCaseError::CONTEXT_MATCHING_ITEM_ID => $duplicateItemId,
					]
				);
			case ItemLabelValidator::CODE_LABEL_SAME_AS_DESCRIPTION:
				$language = $context[ItemLabelValidator::CONTEXT_LANGUAGE];
				throw new UseCaseError(
					UseCaseError::PATCHED_ITEM_LABEL_DESCRIPTION_SAME_VALUE,
					"Label and description for language code {$language} can not have the same value.",
					[ UseCaseError::CONTEXT_LANGUAGE => $context[ItemLabelValidator::CONTEXT_LANGUAGE] ]
				);
			default:
				throw new LogicException( "Unknown validation error: {$validationError->getCode()}" );
		}
	}
}
