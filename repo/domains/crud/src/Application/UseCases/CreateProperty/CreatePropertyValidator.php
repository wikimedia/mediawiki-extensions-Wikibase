<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\CreateProperty;

use LogicException;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\EditMetadataRequestValidatingDeserializer;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Application\Validation\AliasesInLanguageValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\AliasesValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\DescriptionsSyntaxValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\LabelsSyntaxValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\PropertyDescriptionsContentsValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\PropertyDescriptionValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\PropertyLabelsContentsValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\PropertyLabelValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\StatementsValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\StatementValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class CreatePropertyValidator {

	private EditMetadataRequestValidatingDeserializer $editMetadataRequestValidatingDeserializer;
	private array $dataTypesArray;
	private LabelsSyntaxValidator $labelsSyntaxValidator;
	private PropertyLabelsContentsValidator $labelsContentsValidator;
	private DescriptionsSyntaxValidator $descriptionsSyntaxValidator;
	private PropertyDescriptionsContentsValidator $descriptionsContentsValidator;
	private AliasesValidator $aliasesValidator;
	private StatementsValidator $statementsValidator;

	public function __construct(
		EditMetadataRequestValidatingDeserializer $editMetadataRequestValidatingDeserializer,
		array $dataTypesArray,
		LabelsSyntaxValidator $labelsSyntaxValidator,
		PropertyLabelsContentsValidator $labelsContentsValidator,
		DescriptionsSyntaxValidator $descriptionsSyntaxValidator,
		PropertyDescriptionsContentsValidator $descriptionsContentsValidator,
		AliasesValidator $aliasesValidator,
		StatementsValidator $statementsValidator
	) {
		$this->editMetadataRequestValidatingDeserializer = $editMetadataRequestValidatingDeserializer;
		$this->dataTypesArray = $dataTypesArray;
		$this->labelsSyntaxValidator = $labelsSyntaxValidator;
		$this->labelsContentsValidator = $labelsContentsValidator;
		$this->descriptionsSyntaxValidator = $descriptionsSyntaxValidator;
		$this->descriptionsContentsValidator = $descriptionsContentsValidator;
		$this->aliasesValidator = $aliasesValidator;
		$this->statementsValidator = $statementsValidator;
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
		$this->validateAliases( $propertySerialization, '/property' );
		$this->validateStatements( $propertySerialization, '/property' );

		return new DeserializedCreatePropertyRequest(
			new Property(
				null,
				new Fingerprint(
					$this->labelsContentsValidator->getValidatedLabels(),
					$this->descriptionsContentsValidator->getValidatedDescriptions(),
					$this->aliasesValidator->getValidatedAliases()
				),
				$propertySerialization[ 'data_type' ],
				$this->statementsValidator->getValidatedStatements()
			),
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

	private function validateAliases( array $property, string $basePath ): void {
		$aliases = $property['aliases'] ?? [];
		$validationError = $this->aliasesValidator->validate( $aliases, "$basePath/aliases" );

		if ( $validationError ) {
			$context = $validationError->getContext();
			switch ( $validationError->getCode() ) {
				case LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE:
					throw UseCaseError::newInvalidKey( '/property/aliases', $context[LanguageCodeValidator::CONTEXT_LANGUAGE_CODE] );
				case AliasesValidator::CODE_INVALID_VALUE:
					throw UseCaseError::newInvalidValue( $context[AliasesValidator::CONTEXT_PATH] );
				case AliasesInLanguageValidator::CODE_INVALID:
					throw UseCaseError::newInvalidValue( $context[AliasesInLanguageValidator::CONTEXT_PATH] );
				case AliasesInLanguageValidator::CODE_TOO_LONG:
					$path = $context[AliasesInLanguageValidator::CONTEXT_PATH];
					$limit = $context[AliasesInLanguageValidator::CONTEXT_LIMIT];
					throw UseCaseError::newValueTooLong( $path, $limit );
				default:
					throw new LogicException( "Unexpected validation error code: {$validationError->getCode()}" );
			}
		}
	}

	private function validateStatements( array $property, string $basePath ): void {
		$statements = $property[ 'statements' ] ?? [];
		$validationError = $this->statementsValidator->validate( $statements, "$basePath/statements" );

		if ( $validationError ) {
			$context = $validationError->getContext();
			switch ( $validationError->getCode() ) {
				case StatementsValidator::CODE_STATEMENTS_NOT_ASSOCIATIVE:
				case StatementsValidator::CODE_STATEMENT_GROUP_NOT_SEQUENTIAL:
				case StatementsValidator::CODE_STATEMENT_NOT_ARRAY:
					throw UseCaseError::newInvalidValue( $context[ StatementsValidator::CONTEXT_PATH ] );
				case StatementValidator::CODE_INVALID_FIELD:
				case StatementValidator::CODE_INVALID_FIELD_TYPE:
					throw UseCaseError::newInvalidValue( $context[ StatementValidator::CONTEXT_PATH ] );
				case StatementValidator::CODE_PROPERTY_NOT_FOUND:
					throw UseCaseError::newReferencedResourceNotFound( $context[ StatementValidator::CONTEXT_PATH ] );
				case StatementValidator::CODE_MISSING_FIELD:
					throw UseCaseError::newMissingField(
						$context[ StatementValidator::CONTEXT_PATH ],
						$context[ StatementValidator::CONTEXT_FIELD ]
					);
				case StatementsValidator::CODE_PROPERTY_ID_MISMATCH:
					throw new UseCaseError(
						UseCaseError::STATEMENT_GROUP_PROPERTY_ID_MISMATCH,
						"Statement's Property ID does not match the Statement group key",
						[
							UseCaseError::CONTEXT_PATH => $context[ StatementsValidator::CONTEXT_PATH ],
							UseCaseError::CONTEXT_STATEMENT_GROUP_PROPERTY_ID => $context[ StatementsValidator::CONTEXT_PROPERTY_ID_KEY ],
							UseCaseError::CONTEXT_STATEMENT_PROPERTY_ID => $context[ StatementsValidator::CONTEXT_PROPERTY_ID_VALUE ],
						]
					);
				default:
					throw new LogicException( "Unexpected validation error code: {$validationError->getCode()}" );
			}
		}
	}

}
