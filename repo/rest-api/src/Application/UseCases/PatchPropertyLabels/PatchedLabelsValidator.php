<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyLabels;

use LogicException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\Serialization\EmptyLabelException;
use Wikibase\Repo\RestApi\Application\Serialization\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyLabelValidator;

/**
 * @license GPL-2.0-or-later
 */
class PatchedLabelsValidator {

	private LabelsDeserializer $labelsDeserializer;
	private PropertyLabelValidator $labelValidator;
	private LanguageCodeValidator $languageCodeValidator;

	public function __construct(
		LabelsDeserializer $labelsDeserializer,
		PropertyLabelValidator $labelValidator,
		LanguageCodeValidator $languageCodeValidator
	) {
		$this->labelsDeserializer = $labelsDeserializer;
		$this->labelValidator = $labelValidator;
		$this->languageCodeValidator = $languageCodeValidator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( PropertyId $propertyId, TermList $originalLabels, array $labelsSerialization ): TermList {
		$patchedLabels = $this->deserializeLabels( $labelsSerialization );
		foreach ( $this->getModifiedLabels( $originalLabels, $patchedLabels ) as $label ) {
			$this->validateLabel( $propertyId, $label );
			$this->validateLanguageCode( $label );
		}

		return $patchedLabels;
	}

	private function deserializeLabels( array $labelsSerialization ): TermList {
		try {
			$labels = $this->labelsDeserializer->deserialize( $labelsSerialization );
		} catch ( EmptyLabelException $e ) {
			$languageCode = $e->getField();
			throw new UseCaseError(
				UseCaseError::PATCHED_LABEL_EMPTY,
				"Changed label for '$languageCode' cannot be empty",
				[ UseCaseError::CONTEXT_LANGUAGE => $languageCode ]
			);
		} catch ( InvalidFieldException $e ) {
			$languageCode = $e->getField();
			$invalidLabel = json_encode( $e->getValue() );
			throw new UseCaseError(
				UseCaseError::PATCHED_LABEL_INVALID,
				"Changed label for '$languageCode' is invalid: $invalidLabel",
				[
					UseCaseError::CONTEXT_LANGUAGE => $languageCode,
					UseCaseError::CONTEXT_VALUE => $invalidLabel,
				]
			);
		}

		return $labels;
	}

	private function getModifiedLabels( TermList $original, TermList $modified ): array {
		return array_filter(
			iterator_to_array( $modified ),
			fn( Term $label ) => !$original->hasTermForLanguage( $label->getLanguageCode() ) ||
				!$original->getByLanguage( $label->getLanguageCode() )->equals( $label )
		);
	}

	private function validateLabel( PropertyId $propertyId, Term $label ): void {
		$validationError = $this->labelValidator->validate( $propertyId, $label->getLanguageCode(), $label->getText() );
		if ( !$validationError ) {
			return;
		}

		$context = $validationError->getContext();
		switch ( $validationError->getCode() ) {
			case PropertyLabelValidator::CODE_INVALID:
				throw new UseCaseError(
					UseCaseError::PATCHED_LABEL_INVALID,
					"Changed label for '{$label->getLanguageCode()}' is invalid: {$label->getText()}",
					[
						UseCaseError::CONTEXT_LANGUAGE => $label->getLanguageCode(),
						UseCaseError::CONTEXT_VALUE => $label->getText(),
					]
				);
			case PropertyLabelValidator::CODE_TOO_LONG:
				$maxLabelLength = $context[PropertyLabelValidator::CONTEXT_LIMIT];
				throw new UseCaseError(
					UseCaseError::PATCHED_LABEL_TOO_LONG,
					"Changed label for '{$label->getLanguageCode()}' must not be more than $maxLabelLength characters long",
					[
						UseCaseError::CONTEXT_LANGUAGE => $label->getLanguageCode(),
						UseCaseError::CONTEXT_VALUE => $context[PropertyLabelValidator::CONTEXT_LABEL],
						UseCaseError::CONTEXT_CHARACTER_LIMIT => $context[PropertyLabelValidator::CONTEXT_LIMIT],
					]
				);
			case PropertyLabelValidator::CODE_LABEL_DESCRIPTION_EQUAL:
				$language = $context[PropertyLabelValidator::CONTEXT_LANGUAGE];
				throw new UseCaseError(
					UseCaseError::PATCHED_PROPERTY_LABEL_DESCRIPTION_SAME_VALUE,
					"Label and description for language code {$language} can not have the same value.",
					[ UseCaseError::CONTEXT_LANGUAGE => $context[PropertyLabelValidator::CONTEXT_LANGUAGE] ]
				);
			case PropertyLabelValidator::CODE_LABEL_DUPLICATE:
				$language = $context[PropertyLabelValidator::CONTEXT_LANGUAGE];
				$matchingPropertyId = $context[PropertyLabelValidator::CONTEXT_MATCHING_PROPERTY_ID];
				$label = $context[PropertyLabelValidator::CONTEXT_LABEL];
				throw new UseCaseError(
					UseCaseError::PATCHED_PROPERTY_LABEL_DUPLICATE,
					"Property $matchingPropertyId already has label '$label' associated with " .
					"language code '$language'",
					[
						UseCaseError::CONTEXT_LANGUAGE => $language,
						UseCaseError::CONTEXT_LABEL => $label,
						UseCaseError::CONTEXT_MATCHING_PROPERTY_ID => $matchingPropertyId,
					]
				);
			default:
				throw new LogicException( "Unknown validation error: {$validationError->getCode()}" );
		}
	}

	private function validateLanguageCode( Term $label ): void {
		$validationError = $this->languageCodeValidator->validate( $label->getLanguageCode() );
		if ( $validationError ) {
			$languageCode = $validationError->getContext()[LanguageCodeValidator::CONTEXT_LANGUAGE_CODE_VALUE];
			throw new UseCaseError(
				UseCaseError::PATCHED_LABEL_INVALID_LANGUAGE_CODE,
				"Not a valid language code '$languageCode' in changed labels",
				[ UseCaseError::CONTEXT_LANGUAGE => $languageCode ]
			);
		}
	}

}
