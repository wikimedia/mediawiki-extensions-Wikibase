<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchProperty;

use LogicException;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\Utils;
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
use Wikibase\Repo\RestApi\Application\Validation\StatementsValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementValidator;
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
	private StatementsValidator $statementsValidator;

	public function __construct(
		LabelsSyntaxValidator $labelsSyntaxValidator,
		PropertyLabelsContentsValidator $labelsContentsValidator,
		DescriptionsSyntaxValidator $descriptionsSyntaxValidator,
		PropertyDescriptionsContentsValidator $descriptionsContentsValidator,
		AliasesValidator $aliasesValidator,
		StatementsValidator $statementsValidator
	) {
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
	public function validateAndDeserialize(
		array $serialization,
		Property $originalProperty,
		array $originalPropertySerialization
	): Property {
		if ( !isset( $serialization['id'] ) ) { // ignore ID removal
			$serialization['id'] = $originalProperty->getId()->getSerialization();
		}

		$this->assertNoMissingMandatoryFields( $serialization );
		$this->assertNoIllegalModification( $serialization, $originalProperty );
		$this->assertValidFields( $serialization );

		$this->assertValidLabelsAndDescriptions( $originalProperty, $serialization );
		$this->assertValidAliases( $serialization[ 'aliases' ] ?? [] );
		$this->assertValidStatements(
			$serialization['statements'] ?? [],
			$originalProperty,
			$originalPropertySerialization['statements']
		);

		return new Property(
			new NumericPropertyId( $serialization[ 'id' ] ),
			new Fingerprint(
				$this->labelsContentsValidator->getValidatedLabels(),
				$this->descriptionsContentsValidator->getValidatedDescriptions(),
				$this->aliasesValidator->getValidatedAliases()
			),
			$serialization[ 'data_type' ],
			$this->statementsValidator->getValidatedStatements()
		);
	}

	private function assertNoMissingMandatoryFields( array $serialization ): void {
		if ( !isset( $serialization['data_type'] ) ) {
			throw UseCaseError::newMissingFieldInPatchResult( '', 'data_type' );
		}
	}

	private function assertValidFields( array $serialization ): void {
		// 'id' and 'data_type' are not modifiable and 'type' is ignored, so we only check the expected array fields
		foreach ( [ 'labels', 'descriptions', 'aliases', 'statements' ] as $field ) {
			if ( isset( $serialization[$field] ) && !is_array( $serialization[$field] ) ) {
				throw UseCaseError::newPatchResultInvalidValue( "/$field", $serialization[$field] );
			}
		}
	}

	private function assertNoIllegalModification( array $serialization, Property $originalProperty ): void {
		if ( $serialization['id'] !== $originalProperty->getId()->getSerialization() ) {
			throw UseCaseError::newPatchResultModifiedReadOnlyValue( '/id' );
		}

		if ( $serialization['data_type'] !== $originalProperty->getDataTypeId() ) {
			throw UseCaseError::newPatchResultModifiedReadOnlyValue( '/data_type' );
		}
	}

	private function assertValidLabelsAndDescriptions( Property $property, array $serialization ): void {
		$labels = $serialization['labels'] ?? [];
		$descriptions = $serialization['descriptions'] ?? [];
		$validationError = $this->labelsSyntaxValidator->validate( $labels ) ??
			$this->descriptionsSyntaxValidator->validate( $descriptions ) ??
			$this->labelsContentsValidator->validate(
				$this->labelsSyntaxValidator->getPartiallyValidatedLabels(),
				$this->descriptionsSyntaxValidator->getPartiallyValidatedDescriptions(),
				$this->getModifiedLanguages( $property->getLabels(), $this->labelsSyntaxValidator->getPartiallyValidatedLabels() )
			) ??
			$this->descriptionsContentsValidator->validate(
				$this->descriptionsSyntaxValidator->getPartiallyValidatedDescriptions(),
				$this->labelsSyntaxValidator->getPartiallyValidatedLabels(),
				$this->getModifiedLanguages(
					$property->getDescriptions(),
					$this->descriptionsSyntaxValidator->getPartiallyValidatedDescriptions()
				)
			);

		if ( $validationError ) {
			$this->handleLanguageCodeValidationError( $validationError );
			$this->handleLabelsValidationError( $validationError, $labels );
			$this->handleDescriptionsValidationError( $validationError );
			throw new LogicException( "Unknown validation error: {$validationError->getCode()}" );
		}
	}

	private function getModifiedLanguages( TermList $original, TermList $modified ): array {
		return array_keys( array_filter(
			iterator_to_array( $modified ),
			fn( Term $description ) => !$original->hasTermForLanguage( $description->getLanguageCode() ) ||
				!$original->getByLanguage( $description->getLanguageCode() )->equals( $description )
		) );
	}

	private function handleLanguageCodeValidationError( ValidationError $validationError ): void {
		if ( $validationError->getCode() !== LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE ) {
			return;
		}

		$context = $validationError->getContext();
		throw UseCaseError::newPatchResultInvalidKey(
			'/' . $context[LanguageCodeValidator::CONTEXT_FIELD],
			$context[LanguageCodeValidator::CONTEXT_LANGUAGE_CODE]
		);
	}

	private function handleLabelsValidationError( ValidationError $validationError, array $labelsSerialization ): void {
		$context = $validationError->getContext();

		switch ( $validationError->getCode() ) {
			case LabelsSyntaxValidator::CODE_LABELS_NOT_ASSOCIATIVE:
				throw UseCaseError::newPatchResultInvalidValue( '/labels', $labelsSerialization );
			case LabelsSyntaxValidator::CODE_EMPTY_LABEL:
				$languageCode = $context[LabelsSyntaxValidator::CONTEXT_LANGUAGE];
				throw UseCaseError::newPatchResultInvalidValue( "/labels/$languageCode", '' );
			case LabelsSyntaxValidator::CODE_INVALID_LABEL_TYPE:
				$language = $context[LabelsSyntaxValidator::CONTEXT_LANGUAGE];
				$value = $context[LabelsSyntaxValidator::CONTEXT_LABEL];
				throw UseCaseError::newPatchResultInvalidValue( "/labels/$language", $value );
			case PropertyLabelValidator::CODE_INVALID:
				$language = $context[PropertyLabelValidator::CONTEXT_LANGUAGE];
				$value = $context[PropertyLabelValidator::CONTEXT_LABEL];
				throw UseCaseError::newPatchResultInvalidValue( "/labels/$language", $value );
			case PropertyLabelValidator::CODE_TOO_LONG:
				$maxLabelLength = $context[PropertyLabelValidator::CONTEXT_LIMIT];
				$language = $context[PropertyLabelValidator::CONTEXT_LANGUAGE];
				throw UseCaseError::newValueTooLong( "/labels/$language", $maxLabelLength, true );
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
				throw UseCaseError::newPatchResultInvalidValue(
					'/descriptions',
					$context[ DescriptionsSyntaxValidator::CONTEXT_VALUE ]
				);
			case DescriptionsSyntaxValidator::CODE_EMPTY_DESCRIPTION:
				$languageCode = $validationError->getContext()[DescriptionsSyntaxValidator::CONTEXT_LANGUAGE];
				throw UseCaseError::newPatchResultInvalidValue( "/descriptions/$languageCode", '' );
			case DescriptionsSyntaxValidator::CODE_INVALID_DESCRIPTION_TYPE:
				throw UseCaseError::newPatchResultInvalidValue(
					"/descriptions/{$context[DescriptionsSyntaxValidator::CONTEXT_LANGUAGE]}",
					$context[DescriptionsSyntaxValidator::CONTEXT_DESCRIPTION]
				);
			case PropertyDescriptionValidator::CODE_INVALID:
				throw UseCaseError::newPatchResultInvalidValue(
					"/descriptions/{$context[PropertyDescriptionValidator::CONTEXT_LANGUAGE]}",
					$context[PropertyDescriptionValidator::CONTEXT_DESCRIPTION]
				);
			case PropertyDescriptionValidator::CODE_TOO_LONG:
				$languageCode = $context[PropertyDescriptionValidator::CONTEXT_LANGUAGE];
				$maxDescriptionLength = $context[PropertyDescriptionValidator::CONTEXT_LIMIT];
				throw UseCaseError::newValueTooLong( "/descriptions/$languageCode", $maxDescriptionLength, true );
			case PropertyDescriptionValidator::CODE_LABEL_DESCRIPTION_EQUAL:
				throw UseCaseError::newDataPolicyViolation(
					UseCaseError::POLICY_VIOLATION_LABEL_DESCRIPTION_SAME_VALUE,
					[ UseCaseError::CONTEXT_LANGUAGE => $context[PropertyDescriptionValidator::CONTEXT_LANGUAGE] ]
				);
		}
	}

	private function assertValidAliases( array $aliasesSerialization ): void {
		$validationError = $this->aliasesValidator->validate( $aliasesSerialization );
		if ( $validationError ) {
			$errorCode = $validationError->getCode();
			$context = $validationError->getContext();
			switch ( $errorCode ) {
				case LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE:
					throw UseCaseError::newPatchResultInvalidKey( '/aliases', $context[LanguageCodeValidator::CONTEXT_LANGUAGE_CODE] );
				case AliasesValidator::CODE_INVALID_VALUE:
					throw UseCaseError::newPatchResultInvalidValue(
						"/aliases/{$context[AliasesValidator::CONTEXT_PATH]}",
						$context[AliasesValidator::CONTEXT_VALUE]
					);
				case AliasesValidator::CODE_INVALID_ALIASES:
					throw UseCaseError::newPatchResultInvalidValue( '/aliases', $aliasesSerialization );
				case AliasesValidator::CODE_INVALID_ALIAS_LIST:
					$language = $context[AliasesValidator::CONTEXT_LANGUAGE];
					throw UseCaseError::newPatchResultInvalidValue( "/aliases/$language", $aliasesSerialization[$language] );
				case AliasesInLanguageValidator::CODE_INVALID:
					throw UseCaseError::newPatchResultInvalidValue(
						"/aliases/{$context[AliasesInLanguageValidator::CONTEXT_PATH]}",
						$context[AliasesInLanguageValidator::CONTEXT_VALUE]
					);
				case AliasesInLanguageValidator::CODE_TOO_LONG:
					$limit = $context[AliasesInLanguageValidator::CONTEXT_LIMIT];
					$language = $context[AliasesInLanguageValidator::CONTEXT_LANGUAGE];
					$aliasValue = $context[AliasesInLanguageValidator::CONTEXT_VALUE];
					$aliasIndex = Utils::getIndexOfValueInSerialization( $aliasValue, $aliasesSerialization[$language] );
					throw UseCaseError::newValueTooLong( "/aliases/$language/$aliasIndex", $limit, true );
				default:
					throw new LogicException( "Unexpected validation error code: $errorCode" );
			}
		}
	}

	private function assertValidStatements(
		array $statementsSerialization,
		Property $originalProperty,
		array $originalStatementsSerialization
	): void {
		$validationError = $this->statementsValidator->validateModifiedStatements(
			$originalStatementsSerialization,
			$originalProperty->getStatements(),
			$statementsSerialization,
			'/statements'
		);
		if ( $validationError ) {
			$context = $validationError->getContext();
			switch ( $validationError->getCode() ) {
				case StatementsValidator::CODE_STATEMENTS_NOT_ASSOCIATIVE:
				case StatementsValidator::CODE_STATEMENT_GROUP_NOT_SEQUENTIAL:
				case StatementsValidator::CODE_STATEMENT_NOT_ARRAY:
					throw UseCaseError::newPatchResultInvalidValue(
						$context[StatementsValidator::CONTEXT_PATH],
						$context[StatementsValidator::CONTEXT_VALUE]
					);
				case StatementValidator::CODE_INVALID_FIELD_TYPE:
				case StatementValidator::CODE_INVALID_FIELD:
					throw UseCaseError::newPatchResultInvalidValue(
						$context[StatementValidator::CONTEXT_PATH],
						$context[StatementValidator::CONTEXT_VALUE]
					);
				case StatementValidator::CODE_MISSING_FIELD:
					throw UseCaseError::newMissingFieldInPatchResult(
						$context[StatementValidator::CONTEXT_PATH],
						$context[StatementValidator::CONTEXT_FIELD]
					);
				case StatementsValidator::CODE_PROPERTY_ID_MISMATCH:
					throw new UseCaseError(
						UseCaseError::PATCHED_STATEMENT_GROUP_PROPERTY_ID_MISMATCH,
						"Statement's Property ID does not match the statement group key",
						[
							UseCaseError::CONTEXT_PATH => $context[ StatementsValidator::CONTEXT_PATH ],
							UseCaseError::CONTEXT_STATEMENT_GROUP_PROPERTY_ID => $context[ StatementsValidator::CONTEXT_PROPERTY_ID_KEY ],
							UseCaseError::CONTEXT_STATEMENT_PROPERTY_ID => $context[ StatementsValidator::CONTEXT_PROPERTY_ID_VALUE ],
						]
					);
			}
		}

		$originalStatements = $originalProperty->getStatements();
		$patchedStatements = $this->statementsValidator->getValidatedStatements();
		$getStatementIds = fn( StatementList $statementList ) => array_filter( array_map(
			fn( Statement $statement ) => $statement->getGuid(),
			iterator_to_array( $statementList )
		) );

		$originalStatementsIds = $getStatementIds( $originalStatements );
		$patchedStatementsIds = $getStatementIds( $patchedStatements );

		$getStatementIdPath = function( array $serialization, string $id ): string {
			foreach ( $serialization as $propertyId => $statementGroup ) {
				foreach ( $statementGroup as $groupIndex => $statement ) {
					if ( isset( $statement['id'] ) && $statement['id'] === $id ) {
						return "/statements/$propertyId/$groupIndex/id";
					}
				}
			}

			throw new LogicException( "Statement ID '$id' not found in patch result" );
		};

		foreach ( array_count_values( $patchedStatementsIds ) as $id => $occurrence ) {
			if ( $occurrence > 1 || !in_array( $id, $originalStatementsIds ) ) {
				$path = $getStatementIdPath( $statementsSerialization, $id );
				throw UseCaseError::newPatchResultModifiedReadOnlyValue( $path );

			}

			$originalPropertyId = $originalStatements->getFirstStatementWithGuid( $id )->getPropertyId();
			if ( !$patchedStatements->getFirstStatementWithGuid( $id )->getPropertyId()->equals( $originalPropertyId ) ) {
				throw new UseCaseError(
					UseCaseError::PATCHED_STATEMENT_PROPERTY_NOT_MODIFIABLE,
					'Property of a statement cannot be modified',
					[
						UseCaseError::CONTEXT_STATEMENT_ID => $id,
						UseCaseError::CONTEXT_STATEMENT_PROPERTY_ID => $originalPropertyId->getSerialization(),
					]
				);
			}
		}
	}

}
