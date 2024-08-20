<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyDescriptions;

use LogicException;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\DescriptionsSyntaxValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyDescriptionsContentsValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyDescriptionValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class PatchedPropertyDescriptionsValidator {

	private DescriptionsSyntaxValidator $syntaxValidator;
	private PropertyDescriptionsContentsValidator $contentsValidator;

	public function __construct( DescriptionsSyntaxValidator $syntaxValidator, PropertyDescriptionsContentsValidator $contentsValidator ) {
		$this->syntaxValidator = $syntaxValidator;
		$this->contentsValidator = $contentsValidator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize(
		TermList $originalDescriptions,
		TermList $originalLabels,
		array $descriptionsSerialization
	): TermList {
		$error = $this->syntaxValidator->validate( $descriptionsSerialization ) ?:
			$this->contentsValidator->validate(
				$this->syntaxValidator->getPartiallyValidatedDescriptions(),
				$originalLabels,
				$this->getModifiedLanguages( $originalDescriptions, $this->syntaxValidator->getPartiallyValidatedDescriptions() )
			);
		if ( $error ) {
			$this->throwUseCaseError( $error );
		}

		return $this->contentsValidator->getValidatedDescriptions();
	}

	private function getModifiedLanguages( TermList $original, TermList $modified ): array {
		return array_keys( array_filter(
			iterator_to_array( $modified ),
			fn( Term $description ) => !$original->hasTermForLanguage( $description->getLanguageCode() ) ||
				!$original->getByLanguage( $description->getLanguageCode() )->equals( $description )
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
			case DescriptionsSyntaxValidator::CODE_EMPTY_DESCRIPTION:
				$languageCode = $context[DescriptionsSyntaxValidator::CONTEXT_LANGUAGE];
				throw UseCaseError::newPatchResultInvalidValue( "/$languageCode", '' );
			case DescriptionsSyntaxValidator::CODE_INVALID_DESCRIPTION_TYPE:
				throw UseCaseError::newPatchResultInvalidValue(
					"/{$context[DescriptionsSyntaxValidator::CONTEXT_LANGUAGE]}",
					$context[DescriptionsSyntaxValidator::CONTEXT_DESCRIPTION]
				);
			case PropertyDescriptionValidator::CODE_INVALID:
				throw UseCaseError::newPatchResultInvalidValue(
					"/{$context[PropertyDescriptionValidator::CONTEXT_LANGUAGE]}",
					$context[PropertyDescriptionValidator::CONTEXT_DESCRIPTION]
				);
			case PropertyDescriptionValidator::CODE_TOO_LONG:
				$languageCode = $context[PropertyDescriptionValidator::CONTEXT_LANGUAGE];
				$maxDescriptionLength = $context[PropertyDescriptionValidator::CONTEXT_LIMIT];
				throw UseCaseError::newValueTooLong( "/$languageCode", $maxDescriptionLength, true );
			case PropertyDescriptionValidator::CODE_LABEL_DESCRIPTION_EQUAL:
				throw UseCaseError::newDataPolicyViolation(
					UseCaseError::POLICY_VIOLATION_LABEL_DESCRIPTION_SAME_VALUE,
					[ UseCaseError::CONTEXT_LANGUAGE => $context[ PropertyDescriptionValidator::CONTEXT_LANGUAGE ] ]
				);
			default:
				throw new LogicException( "Unknown validation error: {$validationError->getCode()}" );
		}
	}

}
