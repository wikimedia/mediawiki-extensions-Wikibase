<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchProperty;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementsDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\AliasesInLanguageValidator;
use Wikibase\Repo\RestApi\Application\Validation\AliasesValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;

/**
 * @license GPL-2.0-or-later
 */
class PatchedPropertyValidator {

	private LabelsDeserializer $labelsDeserializer;
	private DescriptionsDeserializer $descriptionsDeserializer;
	private AliasesValidator $aliasesValidator;
	private StatementsDeserializer $statementsDeserializer;

	public function __construct(
		LabelsDeserializer $labelsDeserializer,
		DescriptionsDeserializer $descriptionsDeserializer,
		AliasesValidator $aliasesValidator,
		StatementsDeserializer $statementsDeserializer
	) {
		$this->labelsDeserializer = $labelsDeserializer;
		$this->descriptionsDeserializer = $descriptionsDeserializer;
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

		$this->validateAliases( $serialization[ 'aliases' ] ?? [] );

		return new Property(
			new NumericPropertyId( $serialization[ 'id' ] ),
			new Fingerprint(
				$this->labelsDeserializer->deserialize( (array)( $serialization[ 'labels' ] ?? [] ) ),
				$this->descriptionsDeserializer->deserialize( (array)( $serialization[ 'descriptions' ] ?? [] ) ),
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
				throw new UseCaseError(
					UseCaseError::PATCHED_PROPERTY_INVALID_FIELD,
					"Invalid input for '$field' in the patched property",
					[
						UseCaseError::CONTEXT_PATH => $field,
						UseCaseError::CONTEXT_VALUE => $serialization[$field],
					]
				);
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

}
