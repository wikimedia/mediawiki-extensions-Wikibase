<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyLabels;

use LogicException;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\LabelsSyntaxValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyLabelsContentsValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class PatchedPropertyLabelsValidator {

	private LabelsSyntaxValidator $syntaxValidator;
	private PropertyLabelsContentsValidator $contentsValidator;

	public function __construct( LabelsSyntaxValidator $syntaxValidator, PropertyLabelsContentsValidator $contentsValidator ) {
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
				throw UseCaseError::newPatchResultInvalidKey( '', $context[LanguageCodeValidator::CONTEXT_LANGUAGE_CODE] );
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
			case PropertyLabelValidator::CODE_INVALID:
				$language = $context[PropertyLabelValidator::CONTEXT_LANGUAGE];
				$value = $context[PropertyLabelValidator::CONTEXT_LABEL];
				throw new UseCaseError(
					UseCaseError::PATCHED_LABEL_INVALID,
					"Changed label for '{$language}' is invalid: {$value}",
					[ UseCaseError::CONTEXT_LANGUAGE => $language, UseCaseError::CONTEXT_VALUE => $value ]
				);
			case PropertyLabelValidator::CODE_TOO_LONG:
				$maxLabelLength = $context[PropertyLabelValidator::CONTEXT_LIMIT];
				$language = $context[PropertyLabelValidator::CONTEXT_LANGUAGE];
				throw UseCaseError::newValueTooLong( "/$language", $maxLabelLength, true );
			case PropertyLabelValidator::CODE_LABEL_DUPLICATE:
				$language = $context[PropertyLabelValidator::CONTEXT_LANGUAGE];
				$conflictingPropertyId = $context[PropertyLabelValidator::CONTEXT_CONFLICTING_PROPERTY_ID];

				throw UseCaseError::newDataPolicyViolation(
					UseCaseError::POLICY_VIOLATION_PROPERTY_LABEL_DUPLICATE,
					[ UseCaseError::CONTEXT_LANGUAGE => $language, UseCaseError::CONTEXT_CONFLICTING_PROPERTY_ID => $conflictingPropertyId ]
				);
			case PropertyLabelValidator::CODE_LABEL_DESCRIPTION_EQUAL:
				throw UseCaseError::newDataPolicyViolation(
					UseCaseError::POLICY_VIOLATION_LABEL_DESCRIPTION_SAME_VALUE,
					[ UseCaseError::CONTEXT_LANGUAGE => $context[PropertyLabelValidator::CONTEXT_LANGUAGE] ]
				);
			default:
				throw new LogicException( "Unknown validation error: {$validationError->getCode()}" );
		}
	}
}
