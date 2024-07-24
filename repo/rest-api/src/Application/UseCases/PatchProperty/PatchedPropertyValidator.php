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
	public function validateAndDeserialize( array $serialization, Property $originalProperty ): Property {
		if ( !isset( $serialization['id'] ) ) { // ignore ID removal
			$serialization['id'] = $originalProperty->getId()->getSerialization();
		}

		$this->assertNoMissingMandatoryFields( $serialization );
		$this->assertNoIllegalModification( $serialization, $originalProperty );
		$this->assertNoUnexpectedFields( $serialization );
		$this->assertValidFields( $serialization );

		$this->assertValidLabelsAndDescriptions( $originalProperty, $serialization );
		$this->assertValidAliases( $serialization[ 'aliases' ] ?? [] );
		$this->assertValidStatements( $serialization[ 'statements' ] ?? [], $originalProperty );

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
			throw new UseCaseError(
				UseCaseError::PATCHED_PROPERTY_MISSING_FIELD,
				"Mandatory field missing in the patched property: 'data_type'",
				[ UseCaseError::CONTEXT_PATH => 'data_type' ]
			);
		}
	}

	private function assertNoUnexpectedFields( array $serialization ): void {
		$expectedFields = [ 'id', 'data_type', 'type', 'labels', 'descriptions', 'aliases', 'statements' ];

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
		// 'id' and 'data_type' are not modifiable and 'type' is ignored, so we only check the expected array fields
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

		if ( $serialization['data_type'] !== $originalProperty->getDataTypeId() ) {
			throw new UseCaseError(
				UseCaseError::PATCHED_PROPERTY_INVALID_OPERATION_CHANGE_PROPERTY_DATATYPE,
				'Cannot change the datatype of the existing property'
			);
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
			$this->handleDescriptionsValidationError( $validationError, $descriptions );
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
		$languageCode = $context[LanguageCodeValidator::CONTEXT_LANGUAGE_CODE];
		switch ( $context[LanguageCodeValidator::CONTEXT_FIELD] ) {
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
				throw UseCaseError::newValueTooLong( "/labels/$language", $maxLabelLength, true );
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
				$languageCode = $validationError->getContext()[DescriptionsSyntaxValidator::CONTEXT_LANGUAGE];
				throw new UseCaseError(
					UseCaseError::PATCHED_DESCRIPTION_EMPTY,
					"Changed description for '$languageCode' cannot be empty",
					[ UseCaseError::CONTEXT_LANGUAGE => $languageCode ]
				);
			case DescriptionsSyntaxValidator::CODE_INVALID_DESCRIPTION_TYPE:
				$language = $context[DescriptionsSyntaxValidator::CONTEXT_LANGUAGE];
				$value = json_encode( $context[DescriptionsSyntaxValidator::CONTEXT_DESCRIPTION] );
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
				throw UseCaseError::newValueTooLong( "/descriptions/$languageCode", $maxDescriptionLength, true );
			case PropertyDescriptionValidator::CODE_LABEL_DESCRIPTION_EQUAL:
				$language = $context[PropertyDescriptionValidator::CONTEXT_LANGUAGE];
				throw new UseCaseError(
					UseCaseError::PATCHED_PROPERTY_LABEL_DESCRIPTION_SAME_VALUE,
					"Label and description for language code {$language} can not have the same value.",
					[ UseCaseError::CONTEXT_LANGUAGE => $context[PropertyDescriptionValidator::CONTEXT_LANGUAGE] ]
				);
		}
	}

	private function assertValidAliases( array $aliasesSerialization ): void {
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
				case AliasesValidator::CODE_INVALID_ALIASES:
					$this->throwInvalidField( 'aliases', $aliasesSerialization );
				case AliasesValidator::CODE_EMPTY_ALIAS:
					$language = $context[AliasesValidator::CONTEXT_LANGUAGE];
					throw new UseCaseError(
						UseCaseError::PATCHED_ALIAS_EMPTY,
						"Changed alias for '$language' cannot be empty",
						[ UseCaseError::CONTEXT_LANGUAGE => $language ]
					);
				case AliasesValidator::CODE_DUPLICATE_ALIAS:
					$language = $context[AliasesValidator::CONTEXT_LANGUAGE];
					$value = $context[AliasesValidator::CONTEXT_ALIAS];
					throw new UseCaseError(
						UseCaseError::PATCHED_ALIAS_DUPLICATE,
						"Aliases in language '$language' contain duplicate alias: '$value'",
						[ UseCaseError::CONTEXT_LANGUAGE => $language, UseCaseError::CONTEXT_VALUE => $value ]
					);
				case AliasesValidator::CODE_INVALID_ALIAS:
					$language = $context[AliasesValidator::CONTEXT_LANGUAGE];
					$value = $context[AliasesValidator::CONTEXT_ALIAS];
					throw new UseCaseError(
						UseCaseError::PATCHED_ALIASES_INVALID_FIELD,
						"Patched value for '$language' is invalid",
						[ UseCaseError::CONTEXT_PATH => $language, UseCaseError::CONTEXT_VALUE => $value ]
					);
				case AliasesInLanguageValidator::CODE_TOO_LONG:
					$limit = $context[AliasesInLanguageValidator::CONTEXT_LIMIT];
					$language = $context[AliasesInLanguageValidator::CONTEXT_LANGUAGE];
					$aliasValue = $context[AliasesInLanguageValidator::CONTEXT_VALUE];
					$aliasIndex = Utils::getIndexOfValueInSerialization( $aliasValue, $aliasesSerialization[$language] );
					throw UseCaseError::newValueTooLong( "/aliases/$language/$aliasIndex", $limit, true );
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

	private function assertValidStatements( array $statementsSerialization, Property $originalProperty ): void {
		$validationError = $this->statementsValidator->validate( $statementsSerialization );
		if ( $validationError ) {
			$context = $validationError->getContext();
			switch ( $validationError->getCode() ) {
				case StatementsValidator::CODE_STATEMENT_GROUP_NOT_SEQUENTIAL:
					throw new UseCaseError(
						UseCaseError::PATCHED_INVALID_STATEMENT_GROUP_TYPE,
						'Not a valid statement group',
						// TODO: the path will be converted into a proper JSON Pointer in a future task
						[ UseCaseError::CONTEXT_PATH => substr( $context[StatementsValidator::CONTEXT_PATH], 1 ) ]
					);
				case StatementsValidator::CODE_STATEMENT_NOT_ARRAY:
					throw new UseCaseError(
						UseCaseError::PATCHED_INVALID_STATEMENT_TYPE,
						'Not a valid statement type',
						// TODO: the path will be converted into a proper JSON Pointer in a future task
						[ UseCaseError::CONTEXT_PATH => substr( $context[StatementsValidator::CONTEXT_PATH], 1 ) ]
					);
				case StatementsValidator::CODE_STATEMENTS_NOT_ASSOCIATIVE:
					$this->throwInvalidField( 'statements', $context[ StatementsValidator::CONTEXT_STATEMENTS ] );
				case StatementsValidator::CODE_INVALID_STATEMENT_DATA:
					$field = $context[ StatementsValidator::CONTEXT_FIELD ];
					throw new UseCaseError(
						UseCaseError::PATCHED_STATEMENT_INVALID_FIELD,
						"Invalid input for '{$field}' in the patched statement",
						[
							UseCaseError::CONTEXT_PATH => $field,
							UseCaseError::CONTEXT_VALUE => $context[ StatementsValidator::CONTEXT_VALUE ],
						]
					);
				case StatementsValidator::CODE_MISSING_STATEMENT_DATA:
					$field = $context[ StatementsValidator::CONTEXT_FIELD ];
					throw new UseCaseError(
						UseCaseError::PATCHED_STATEMENT_MISSING_FIELD,
						"Mandatory field missing in the patched statement: {$field}",
						[ UseCaseError::CONTEXT_PATH => $field ]
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

		foreach ( array_count_values( $patchedStatementsIds ) as $id => $occurrence ) {
			if ( $occurrence > 1 || !in_array( $id, $originalStatementsIds ) ) {
				throw new UseCaseError(
					UseCaseError::STATEMENT_ID_NOT_MODIFIABLE,
					'Statement IDs cannot be created or modified',
					[ UseCaseError::CONTEXT_STATEMENT_ID => $id ]
				);
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
