<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItemAliases;

use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\DuplicateAliasException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\EmptyAliasException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\Utils;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\AliasesInLanguageValidator;
use Wikibase\Repo\RestApi\Application\Validation\AliasLanguageCodeValidator;

/**
 * @license GPL-2.0-or-later
 */
class PatchedAliasesValidator {

	private AliasesDeserializer $aliasesDeserializer;
	private AliasesInLanguageValidator $aliasesInLanguageValidator;
	private AliasLanguageCodeValidator $languageCodeValidator;

	public function __construct(
		AliasesDeserializer $aliasesDeserializer,
		AliasesInLanguageValidator $aliasesInLanguageValidator,
		AliasLanguageCodeValidator $languageCodeValidator
	) {
		$this->aliasesDeserializer = $aliasesDeserializer;
		$this->aliasesInLanguageValidator = $aliasesInLanguageValidator;
		$this->languageCodeValidator = $languageCodeValidator;
	}

	/**
	 * @param mixed $serialization
	 *
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( $serialization ): AliasGroupList {
		if ( !is_array( $serialization ) || ( array_is_list( $serialization ) && count( $serialization ) > 0 ) ) {
			throw new UseCaseError(
				UseCaseError::PATCHED_ALIASES_INVALID_FIELD,
				"Patched value for '' is invalid",
				[ UseCaseError::CONTEXT_PATH => '', UseCaseError::CONTEXT_VALUE => $serialization ]
			);
		}

		$this->validateLanguageCodes( array_keys( $serialization ) );

		$aliases = $this->deserialize( $serialization );

		$this->validateAliases( $aliases );

		return $aliases;
	}

	private function deserialize( array $serialization ): AliasGroupList {
		try {
			return $this->aliasesDeserializer->deserialize( $serialization );
		} catch ( EmptyAliasException $e ) {
			throw new UseCaseError(
				UseCaseError::PATCHED_ALIAS_EMPTY,
				"Changed alias for '{$e->getLanguage()}' cannot be empty",
				[ UseCaseError::CONTEXT_LANGUAGE => $e->getLanguage() ]
			);
		} catch ( DuplicateAliasException $e ) {
			throw new UseCaseError(
				UseCaseError::PATCHED_ALIAS_DUPLICATE,
				"Aliases in language '{$e->getField()}' contain duplicate alias: '{$e->getValue()}'",
				[ UseCaseError::CONTEXT_LANGUAGE => $e->getField(), UseCaseError::CONTEXT_VALUE => $e->getValue() ]
			);
		} catch ( InvalidFieldException $e ) {
			throw new UseCaseError(
				UseCaseError::PATCHED_ALIASES_INVALID_FIELD,
				"Patched value for '{$e->getField()}' is invalid",
				[ UseCaseError::CONTEXT_PATH => $e->getPath(), UseCaseError::CONTEXT_VALUE => $e->getValue() ]
			);
		}
	}

	private function validateAliases( AliasGroupList $aliases ): void {
		foreach ( $aliases as $aliasGroup ) {
			$validationError = $this->aliasesInLanguageValidator->validate( $aliasGroup );
			if ( $validationError ) {
				$context = $validationError->getContext();
				switch ( $validationError->getCode() ) {
					case AliasesInLanguageValidator::CODE_TOO_LONG:
						$limit = $context[AliasesInLanguageValidator::CONTEXT_LIMIT];
						$aliasValue = $context[AliasesInLanguageValidator::CONTEXT_VALUE];
						$aliasIndex = Utils::getIndexOfValueInSerialization( $aliasValue, $aliasGroup->getAliases() );
						throw UseCaseError::newValueTooLong( "/{$aliasGroup->getLanguageCode()}/$aliasIndex", $limit, true );
					default:
						$value = $context[AliasesInLanguageValidator::CONTEXT_VALUE];
						$path = $context[AliasesInLanguageValidator::CONTEXT_PATH];
						throw new UseCaseError(
							UseCaseError::PATCHED_ALIASES_INVALID_FIELD,
							"Patched value for '{$aliasGroup->getLanguageCode()}' is invalid",
							[
								UseCaseError::CONTEXT_PATH => $path,
								UseCaseError::CONTEXT_VALUE => $value,
							]
						);
				}
			}
		}
	}

	private function validateLanguageCodes( array $languageCodes ): void {
		foreach ( $languageCodes as $languageCode ) {
			if ( $this->languageCodeValidator->validate( $languageCode ) ) {
				throw new UseCaseError(
					UseCaseError::PATCHED_ALIASES_INVALID_LANGUAGE_CODE,
					"Not a valid language code '$languageCode' in changed aliases",
					[ UseCaseError::CONTEXT_LANGUAGE => $languageCode ]
				);
			}
		}
	}

}
