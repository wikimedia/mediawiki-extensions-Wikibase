<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\CreateProperty;

use LogicException;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyDeserializer;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\EditMetadataRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\DescriptionsSyntaxValidator;
use Wikibase\Repo\RestApi\Application\Validation\LabelsSyntaxValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyDescriptionsContentsValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyDescriptionValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyLabelsContentsValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class CreatePropertyValidator {

	private PropertyDeserializer $propertyDeserializer;
	private EditMetadataRequestValidatingDeserializer $editMetadataRequestValidatingDeserializer;
	private array $dataTypesArray;
	private LabelsSyntaxValidator $labelsSyntaxValidator;
	private PropertyLabelsContentsValidator $labelsContentsValidator;
	private DescriptionsSyntaxValidator $descriptionsSyntaxValidator;
	private PropertyDescriptionsContentsValidator $descriptionsContentsValidator;

	public function __construct(
		PropertyDeserializer $propertyDeserializer,
		EditMetadataRequestValidatingDeserializer $editMetadataRequestValidatingDeserializer,
		array $dataTypesArray,
		LabelsSyntaxValidator $labelsSyntaxValidator,
		PropertyLabelsContentsValidator $labelsContentsValidator,
		DescriptionsSyntaxValidator $descriptionsSyntaxValidator,
		PropertyDescriptionsContentsValidator $descriptionsContentsValidator
	) {
		$this->propertyDeserializer = $propertyDeserializer;
		$this->editMetadataRequestValidatingDeserializer = $editMetadataRequestValidatingDeserializer;
		$this->dataTypesArray = $dataTypesArray;
		$this->labelsSyntaxValidator = $labelsSyntaxValidator;
		$this->labelsContentsValidator = $labelsContentsValidator;
		$this->descriptionsSyntaxValidator = $descriptionsSyntaxValidator;
		$this->descriptionsContentsValidator = $descriptionsContentsValidator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( CreatePropertyRequest $request ): DeserializedCreatePropertyRequest {
		$propertySerialization = $request->getProperty();
		if ( !isset( $propertySerialization['data_type'] ) ) {
			throw UseCaseError::newMissingField( '/property', 'data_type' );
		}

		$this->validateTopLevelFields( $propertySerialization );
		$this->validateLabelsAndDescriptions( $propertySerialization, '/property' );

		return new DeserializedCreatePropertyRequest(
			$this->propertyDeserializer->deserialize( $request->getProperty() ),
			$this->editMetadataRequestValidatingDeserializer->validateAndDeserialize( $request )
		);
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateTopLevelFields( array $property ): void {
		if (
			!is_string( $property['data_type'] ) ||
			!in_array( $property['data_type'], $this->dataTypesArray )
		) {
			throw UseCaseError::newInvalidValue( '/property/data_type' );
		}

		foreach ( [ 'labels', 'descriptions', 'aliases', 'statements' ] as $arrayField ) {
			if ( isset( $property[$arrayField] ) && !is_array( $property[$arrayField] ) ) {
				throw UseCaseError::newInvalidValue( "/property/$arrayField" );
			}
		}
	}

	private function validateLabelsAndDescriptions( array $property, string $basePath ): void {
		$labels = $property['labels'] ?? [];
		$descriptions = $property['descriptions'] ?? [];
		$validationError = $this->labelsSyntaxValidator->validate( $labels, "$basePath/labels" ) ??
						   $this->descriptionsSyntaxValidator->validate( $descriptions, "$basePath/descriptions" ) ??
						   $this->labelsContentsValidator->validate(
							   $this->labelsSyntaxValidator->getPartiallyValidatedLabels(),
							   $this->descriptionsSyntaxValidator->getPartiallyValidatedDescriptions(),
						   ) ??
						   $this->descriptionsContentsValidator->validate(
							   $this->descriptionsSyntaxValidator->getPartiallyValidatedDescriptions(),
							   $this->labelsSyntaxValidator->getPartiallyValidatedLabels(),
						   );

		if ( $validationError ) {
			$this->handleLanguageCodeValidationError( $validationError );
			$this->handleLabelsValidationError( $validationError );
			$this->handleDescriptionsValidationError( $validationError );
			throw new LogicException( "Unknown validation error: {$validationError->getCode()}" );
		}
	}

	private function handleLanguageCodeValidationError( ValidationError $validationError ): void {
		if ( $validationError->getCode() !== LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE ) {
			return;
		}

		$context = $validationError->getContext();
		throw UseCaseError::newInvalidKey(
			$context[LanguageCodeValidator::CONTEXT_PATH],
			$context[LanguageCodeValidator::CONTEXT_LANGUAGE_CODE]
		);
	}

	private function handleLabelsValidationError( ValidationError $validationError ): void {
		$context = $validationError->getContext();

		switch ( $validationError->getCode() ) {
			case LabelsSyntaxValidator::CODE_LABELS_NOT_ASSOCIATIVE:
				throw UseCaseError::newInvalidValue( '/property/labels' );
			case LabelsSyntaxValidator::CODE_EMPTY_LABEL:
				$languageCode = $context[LabelsSyntaxValidator::CONTEXT_LANGUAGE];
				throw UseCaseError::newInvalidValue( "/property/labels/$languageCode" );
			case LabelsSyntaxValidator::CODE_INVALID_LABEL_TYPE:
				$language = $context[LabelsSyntaxValidator::CONTEXT_LANGUAGE];
				throw UseCaseError::newInvalidValue( "/property/labels/$language" );
			case PropertyLabelValidator::CODE_INVALID:
				$language = $context[PropertyLabelValidator::CONTEXT_LANGUAGE];
				throw UseCaseError::newInvalidValue( "/property/labels/$language" );
			case PropertyLabelValidator::CODE_TOO_LONG:
				$maxLabelLength = $context[PropertyLabelValidator::CONTEXT_LIMIT];
				$language = $context[PropertyLabelValidator::CONTEXT_LANGUAGE];
				throw UseCaseError::newValueTooLong( "/property/labels/$language", $maxLabelLength );
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
		}
	}

	private function handleDescriptionsValidationError( ValidationError $validationError ): void {
		$context = $validationError->getContext();
		switch ( $validationError->getCode() ) {
			case DescriptionsSyntaxValidator::CODE_DESCRIPTIONS_NOT_ASSOCIATIVE:
				throw UseCaseError::newInvalidValue( '/property/descriptions' );
			case DescriptionsSyntaxValidator::CODE_EMPTY_DESCRIPTION:
				$languageCode = $validationError->getContext()[DescriptionsSyntaxValidator::CONTEXT_LANGUAGE];
				throw UseCaseError::newInvalidValue( "/property/descriptions/$languageCode" );
			case DescriptionsSyntaxValidator::CODE_INVALID_DESCRIPTION_TYPE:
				throw UseCaseError::newInvalidValue(
					"/property/descriptions/{$context[DescriptionsSyntaxValidator::CONTEXT_LANGUAGE]}"
				);
			case PropertyDescriptionValidator::CODE_INVALID:
				throw UseCaseError::newInvalidValue(
					"/property/descriptions/{$context[PropertyDescriptionValidator::CONTEXT_LANGUAGE]}"
				);
			case PropertyDescriptionValidator::CODE_TOO_LONG:
				$languageCode = $context[PropertyDescriptionValidator::CONTEXT_LANGUAGE];
				$maxDescriptionLength = $context[PropertyDescriptionValidator::CONTEXT_LIMIT];
				throw UseCaseError::newValueTooLong( "/property/descriptions/$languageCode", $maxDescriptionLength );
			case PropertyDescriptionValidator::CODE_LABEL_DESCRIPTION_EQUAL:
				throw UseCaseError::newDataPolicyViolation(
					UseCaseError::POLICY_VIOLATION_LABEL_DESCRIPTION_SAME_VALUE,
					[ UseCaseError::CONTEXT_LANGUAGE => $context[PropertyDescriptionValidator::CONTEXT_LANGUAGE] ]
				);
		}
	}

}
