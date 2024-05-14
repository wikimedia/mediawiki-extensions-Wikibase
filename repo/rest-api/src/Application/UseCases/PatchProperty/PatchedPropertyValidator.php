<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchProperty;

use LogicException;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Repo\RestApi\Application\Serialization\StatementsDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\AliasesInLanguageValidator;
use Wikibase\Repo\RestApi\Application\Validation\AliasesValidator;
use Wikibase\Repo\RestApi\Application\Validation\DescriptionsSyntaxValidator;
use Wikibase\Repo\RestApi\Application\Validation\LabelsSyntaxValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyDescriptionsContentsValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyDescriptionValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyLabelsContentsValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

// disable because it forces comments for switch-cases that look like fall-throughs but aren't
// phpcs:disable PSR2.ControlStructures.SwitchDeclaration.TerminatingComment

/**
 * @license GPL-2.0-or-later
 */
class PatchedPropertyValidator {

	private LabelsSyntaxValidator $labelsSyntaxValidator;
	private PropertyLabelsContentsValidator $labelsContentsValidator;
	private DescriptionsSyntaxValidator $descriptionsSyntaxValidator;
	private PropertyDescriptionsContentsValidator $descriptionsContentsValidator;
	private AliasesValidator $aliasesValidator;
	private StatementsDeserializer $statementsDeserializer;

	public function __construct(
		LabelsSyntaxValidator $labelsSyntaxValidator,
		PropertyLabelsContentsValidator $labelsContentsValidator,
		DescriptionsSyntaxValidator $descriptionsSyntaxValidator,
		PropertyDescriptionsContentsValidator $descriptionsContentsValidator,
		AliasesValidator $aliasesValidator,
		StatementsDeserializer $statementsDeserializer
	) {
		$this->labelsSyntaxValidator = $labelsSyntaxValidator;
		$this->labelsContentsValidator = $labelsContentsValidator;
		$this->descriptionsSyntaxValidator = $descriptionsSyntaxValidator;
		$this->descriptionsContentsValidator = $descriptionsContentsValidator;
		$this->aliasesValidator = $aliasesValidator;
		$this->statementsDeserializer = $statementsDeserializer;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( array $serialization, Property $originalProperty ): Property {
		if ( !isset( $serialization['id'] ) ) { // ignore ID removal
			$serialization['id'] = $originalProperty->getId()->getSerialization();
		}

		$this->assertNoMissingMandatoryFields( $serialization );
		$this->assertNoIllegalModification( $serialization, $originalProperty );
		$this->assertNoUnexpectedFields( $serialization );
		$this->assertValidFields( $serialization );
		$this->assertValidLabelsAndDescriptions( $serialization );

		$this->validateAliases( $serialization[ 'aliases' ] ?? [] );

		return new Property(
			new NumericPropertyId( $serialization[ 'id' ] ),
			new Fingerprint(
				$this->labelsContentsValidator->getValidatedLabels(),
				$this->descriptionsContentsValidator->getValidatedDescriptions(),
				$this->aliasesValidator->getValidatedAliases()
			),
			$serialization[ 'data-type' ],
			$this->statementsDeserializer->deserialize( (array)( $serialization[ 'statements' ] ?? [] ) )
		);
	}

	private function assertNoMissingMandatoryFields( array $serialization ): void {
		if ( !isset( $serialization['data-type'] ) ) {
			throw new UseCaseError(
				UseCaseError::PATCHED_PROPERTY_MISSING_FIELD,
				"Mandatory field missing in the patched property: 'data-type'",
				[ UseCaseError::CONTEXT_PATH => 'data-type' ]
			);
		}
	}

	private function assertNoUnexpectedFields( array $serialization ): void {
		$expectedFields = [ 'id', 'data-type', 'type', 'labels', 'descriptions', 'aliases', 'statements' ];

		foreach ( array_keys( $serialization ) as $field ) {
			if ( !in_array( $field, $expectedFields ) ) {
				throw new UseCaseError(
					UseCaseError::PATCHED_PROPERTY_UNEXPECTED_FIELD,
					"The patched property contains an unexpected field: '$field'"
				);
			}
		}
	}

	private function assertValidFields( array $serialization ): void {
		// 'id' and 'data-type' are not modifiable and 'type' is ignored, so we only check the expected array fields
		foreach ( [ 'labels', 'descriptions', 'aliases', 'statements' ] as $field ) {
			if ( isset( $serialization[$field] ) && !is_array( $serialization[$field] ) ) {
				$this->throwInvalidField( $field, $serialization[$field] );
			}
		}
	}

	private function assertNoIllegalModification( array $serialization, Property $originalProperty ): void {
		if ( $serialization['id'] !== $originalProperty->getId()->getSerialization() ) {
			throw new UseCaseError(
				UseCaseError::PATCHED_PROPERTY_INVALID_OPERATION_CHANGE_PROPERTY_ID,
				'Cannot change the ID of the existing property'
			);
		}

		if ( $serialization['data-type'] !== $originalProperty->getDataTypeId() ) {
			throw new UseCaseError(
				UseCaseError::PATCHED_PROPERTY_INVALID_OPERATION_CHANGE_PROPERTY_DATATYPE,
				'Cannot change the datatype of the existing property'
			);
		}
	}

	private function assertValidLabelsAndDescriptions( array $serialization ): void {
		$labels = $serialization['labels'] ?? [];
		$descriptions = $serialization['descriptions'] ?? [];
		$propertyId = new NumericPropertyId( $serialization['id'] );
		$validationError = $this->labelsSyntaxValidator->validate( $labels )
			?? $this->descriptionsSyntaxValidator->validate( $descriptions )
			?? $this->labelsContentsValidator->validate( $this->labelsSyntaxValidator->getPartiallyValidatedLabels(), $propertyId )
			?? $this->descriptionsContentsValidator->validate(
				$this->descriptionsSyntaxValidator->getPartiallyValidatedDescriptions(),
				$propertyId
			);

		if ( $validationError ) {
			$this->handleLanguageCodeValidationError( $validationError );
			$this->handleLabelsValidationError( $validationError, $labels );
			$this->handleDescriptionsValidationError( $validationError, $descriptions );
			throw new LogicException( "Unknown validation error: {$validationError->getCode()}" );
		}
	}

	private function handleLanguageCodeValidationError( ValidationError $validationError ): void {
		if ( $validationError->getCode() !== LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE ) {
			return;
		}

		$context = $validationError->getContext();
		$languageCode = $context[LanguageCodeValidator::CONTEXT_LANGUAGE_CODE];
		switch ( $context[LanguageCodeValidator::CONTEXT_PATH] ) {
			case 'labels':
				throw new UseCaseError(
					UseCaseError::PATCHED_LABEL_INVALID_LANGUAGE_CODE,
					"Not a valid language code '$languageCode' in changed labels",
					[ UseCaseError::CONTEXT_LANGUAGE => $languageCode ]
				);
			case 'descriptions':
				throw new UseCaseError(
					UseCaseError::PATCHED_DESCRIPTION_INVALID_LANGUAGE_CODE,
					"Not a valid language code '$languageCode' in changed descriptions",
					[ UseCaseError::CONTEXT_LANGUAGE => $languageCode ]
				);
		}
	}

	private function handleLabelsValidationError( ValidationError $validationError, array $labelsSerialization ): void {
		$context = $validationError->getContext();

		switch ( $validationError->getCode() ) {
			case LabelsSyntaxValidator::CODE_LABELS_NOT_ASSOCIATIVE:
				$this->throwInvalidField( 'labels', $labelsSerialization );
			case LabelsSyntaxValidator::CODE_EMPTY_LABEL:
				$languageCode = $validationError->getContext()[LabelsSyntaxValidator::CONTEXT_FIELD_LANGUAGE];
				throw new UseCaseError(
					UseCaseError::PATCHED_LABEL_EMPTY,
					"Changed label for '$languageCode' cannot be empty",
					[ UseCaseError::CONTEXT_LANGUAGE => $languageCode ]
				);
			case LabelsSyntaxValidator::CODE_INVALID_LABEL_TYPE:
				$language = $context[LabelsSyntaxValidator::CONTEXT_FIELD_LANGUAGE];
				$value = json_encode( $context[LabelsSyntaxValidator::CONTEXT_FIELD_LABEL] );
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
				throw new UseCaseError(
					UseCaseError::PATCHED_LABEL_TOO_LONG,
					"Changed label for '{$language}' must not be more than $maxLabelLength characters long",
					[
						UseCaseError::CONTEXT_LANGUAGE => $context[PropertyLabelValidator::CONTEXT_LANGUAGE],
						UseCaseError::CONTEXT_VALUE => $context[PropertyLabelValidator::CONTEXT_LABEL],
						UseCaseError::CONTEXT_CHARACTER_LIMIT => $context[PropertyLabelValidator::CONTEXT_LIMIT],
					]
				);
			case PropertyLabelValidator::CODE_LABEL_DUPLICATE:
				$language = $context[PropertyLabelValidator::CONTEXT_LANGUAGE];
				$label = $context[PropertyLabelValidator::CONTEXT_LABEL];
				$matchingPropertyId = $context[PropertyLabelValidator::CONTEXT_MATCHING_PROPERTY_ID];
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
			case PropertyLabelValidator::CODE_LABEL_DESCRIPTION_EQUAL:
				$language = $context[PropertyLabelValidator::CONTEXT_LANGUAGE];
				throw new UseCaseError(
					UseCaseError::PATCHED_PROPERTY_LABEL_DESCRIPTION_SAME_VALUE,
					"Label and description for language code {$language} can not have the same value.",
					[ UseCaseError::CONTEXT_LANGUAGE => $context[PropertyLabelValidator::CONTEXT_LANGUAGE] ]
				);
		}
	}

	private function handleDescriptionsValidationError( ValidationError $validationError, array $descriptionsSerialization ): void {
		$context = $validationError->getContext();
		switch ( $validationError->getCode() ) {
			case DescriptionsSyntaxValidator::CODE_DESCRIPTIONS_NOT_ASSOCIATIVE:
				$this->throwInvalidField( 'descriptions', $descriptionsSerialization );
			case DescriptionsSyntaxValidator::CODE_EMPTY_DESCRIPTION:
				$languageCode = $validationError->getContext()[DescriptionsSyntaxValidator::CONTEXT_FIELD_LANGUAGE];
				throw new UseCaseError(
					UseCaseError::PATCHED_DESCRIPTION_EMPTY,
					"Changed description for '$languageCode' cannot be empty",
					[ UseCaseError::CONTEXT_LANGUAGE => $languageCode ]
				);
			case DescriptionsSyntaxValidator::CODE_INVALID_DESCRIPTION_TYPE:
				$language = $context[DescriptionsSyntaxValidator::CONTEXT_FIELD_LANGUAGE];
				$value = json_encode( $context[DescriptionsSyntaxValidator::CONTEXT_FIELD_DESCRIPTION] );
				throw new UseCaseError(
					UseCaseError::PATCHED_DESCRIPTION_INVALID,
					"Changed description for '{$language}' is invalid: {$value}",
					[ UseCaseError::CONTEXT_LANGUAGE => $language, UseCaseError::CONTEXT_VALUE => $value ]
				);
			case PropertyDescriptionValidator::CODE_INVALID:
				$language = $context[PropertyDescriptionValidator::CONTEXT_LANGUAGE];
				$value = $context[PropertyDescriptionValidator::CONTEXT_DESCRIPTION];
				throw new UseCaseError(
					UseCaseError::PATCHED_DESCRIPTION_INVALID,
					"Changed description for '{$language}' is invalid: {$value}",
					[ UseCaseError::CONTEXT_LANGUAGE => $language, UseCaseError::CONTEXT_VALUE => $value ]
				);
			case PropertyDescriptionValidator::CODE_TOO_LONG:
				$languageCode = $context[PropertyDescriptionValidator::CONTEXT_LANGUAGE];
				$maxDescriptionLength = $context[PropertyDescriptionValidator::CONTEXT_LIMIT];
				throw new UseCaseError(
					UseCaseError::PATCHED_DESCRIPTION_TOO_LONG,
					"Changed description for '$languageCode' must not be more than $maxDescriptionLength characters long",
					[
						UseCaseError::CONTEXT_LANGUAGE => $languageCode,
						UseCaseError::CONTEXT_VALUE => $context[PropertyDescriptionValidator::CONTEXT_DESCRIPTION],
						UseCaseError::CONTEXT_CHARACTER_LIMIT => $context[PropertyDescriptionValidator::CONTEXT_LIMIT],
					]
				);
			case PropertyDescriptionValidator::CODE_LABEL_DESCRIPTION_EQUAL:
				$language = $context[PropertyDescriptionValidator::CONTEXT_LANGUAGE];
				throw new UseCaseError(
					UseCaseError::PATCHED_PROPERTY_LABEL_DESCRIPTION_SAME_VALUE,
					"Label and description for language code {$language} can not have the same value.",
					[ UseCaseError::CONTEXT_LANGUAGE => $context[PropertyDescriptionValidator::CONTEXT_LANGUAGE] ]
				);
		}
	}

	/**
	 * @param mixed $aliasesSerialization
	 */
	private function validateAliases( $aliasesSerialization ): void {
		if (
			!is_array( $aliasesSerialization ) ||
			count( $aliasesSerialization ) && array_is_list( $aliasesSerialization )
		) {
			throw new UseCaseError(
				UseCaseError::PATCHED_ALIASES_INVALID_FIELD,
				"Patched value for 'aliases' is invalid",
				[ UseCaseError::CONTEXT_PATH => '', UseCaseError::CONTEXT_VALUE => $aliasesSerialization ]
			);
		}

		$validationError = $this->aliasesValidator->validate( $aliasesSerialization );
		if ( $validationError ) {
			$context = $validationError->getContext();
			switch ( $validationError->getCode() ) {
				case LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE:
					$language = $context[LanguageCodeValidator::CONTEXT_LANGUAGE_CODE];
					throw new UseCaseError(
						UseCaseError::PATCHED_ALIASES_INVALID_LANGUAGE_CODE,
						"Not a valid language code '$language' in changed aliases",
						[ UseCaseError::CONTEXT_LANGUAGE => $language ]
					);
				case AliasesValidator::CODE_EMPTY_ALIAS:
					$language = $context[AliasesValidator::CONTEXT_FIELD_LANGUAGE];
					throw new UseCaseError(
						UseCaseError::PATCHED_ALIAS_EMPTY,
						"Changed alias for '$language' cannot be empty",
						[ UseCaseError::CONTEXT_LANGUAGE => $language ]
					);
				case AliasesValidator::CODE_DUPLICATE_ALIAS:
					$language = $context[AliasesValidator::CONTEXT_FIELD_LANGUAGE];
					$value = $context[AliasesValidator::CONTEXT_FIELD_ALIAS];
					throw new UseCaseError(
						UseCaseError::PATCHED_ALIAS_DUPLICATE,
						"Aliases in language '$language' contain duplicate alias: '$value'",
						[ UseCaseError::CONTEXT_LANGUAGE => $language, UseCaseError::CONTEXT_VALUE => $value ]
					);
				case AliasesValidator::CODE_INVALID_ALIAS:
					$language = $context[AliasesValidator::CONTEXT_FIELD_LANGUAGE];
					$value = $context[AliasesValidator::CONTEXT_FIELD_ALIAS];
					throw new UseCaseError(
						UseCaseError::PATCHED_ALIASES_INVALID_FIELD,
						"Patched value for '$language' is invalid",
						[ UseCaseError::CONTEXT_PATH => $language, UseCaseError::CONTEXT_VALUE => $value ]
					);
				case AliasesInLanguageValidator::CODE_TOO_LONG:
					$limit = $context[AliasesInLanguageValidator::CONTEXT_LIMIT];
					$language = $context[AliasesInLanguageValidator::CONTEXT_LANGUAGE];
					throw new UseCaseError(
						UseCaseError::PATCHED_ALIAS_TOO_LONG,
						"Changed alias for '$language' must not be more than $limit characters long",
						[
							UseCaseError::CONTEXT_LANGUAGE => $language,
							UseCaseError::CONTEXT_VALUE => $context[AliasesInLanguageValidator::CONTEXT_VALUE],
							UseCaseError::CONTEXT_CHARACTER_LIMIT => $limit,
						]
					);
				default:
					throw new UseCaseError(
						UseCaseError::PATCHED_ALIASES_INVALID_FIELD,
						"Patched value for '{$context[AliasesInLanguageValidator::CONTEXT_LANGUAGE]}' is invalid",
						[
							UseCaseError::CONTEXT_PATH => $context[AliasesInLanguageValidator::CONTEXT_PATH],
							UseCaseError::CONTEXT_VALUE => $context[AliasesInLanguageValidator::CONTEXT_VALUE],
						]
					);
			}
		}
	}

	/**
	 * @param string $field
	 * @param mixed $value
	 *
	 * @return never
	 */
	private function throwInvalidField( string $field, $value ): void {
		throw new UseCaseError(
			UseCaseError::PATCHED_PROPERTY_INVALID_FIELD,
			"Invalid input for '$field' in the patched property",
			[
				UseCaseError::CONTEXT_PATH => $field,
				UseCaseError::CONTEXT_VALUE => $value,
			]
		);
	}

}
