<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItemAliases;

use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\DuplicateAliasException;
use Wikibase\Repo\RestApi\Application\Serialization\EmptyAliasException;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\AliasesInLanguageValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;

/**
 * @license GPL-2.0-or-later
 */
class PatchedAliasesValidator {

	private AliasesDeserializer $aliasesDeserializer;
	private AliasesInLanguageValidator $aliasesInLanguageValidator;
	private LanguageCodeValidator $languageCodeValidator;

	public function __construct(
		AliasesDeserializer $aliasesDeserializer,
		AliasesInLanguageValidator $aliasesInLanguageValidator,
		LanguageCodeValidator $languageCodeValidator
	) {
		$this->aliasesDeserializer = $aliasesDeserializer;
		$this->aliasesInLanguageValidator = $aliasesInLanguageValidator;
		$this->languageCodeValidator = $languageCodeValidator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( array $serialization ): AliasGroupList {
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
				"Changed alias for '{$e->getField()}' cannot be empty",
				[ UseCaseError::CONTEXT_LANGUAGE => $e->getField() ]
			);
		} catch ( DuplicateAliasException $e ) {
			throw new UseCaseError(
				UseCaseError::PATCHED_ALIAS_DUPLICATE,
				"Aliases in language '{$e->getField()}' contain duplicate alias: '{$e->getValue()}'",
				[ UseCaseError::CONTEXT_LANGUAGE => $e->getField(), UseCaseError::CONTEXT_VALUE => $e->getValue() ]
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
						throw new UseCaseError(
							UseCaseError::PATCHED_ALIAS_TOO_LONG,
							"Changed alias for '{$aliasGroup->getLanguageCode()}' must not be more than '$limit'",
							[
								UseCaseError::CONTEXT_LANGUAGE => $aliasGroup->getLanguageCode(),
								UseCaseError::CONTEXT_VALUE => $context[AliasesInLanguageValidator::CONTEXT_VALUE],
								UseCaseError::CONTEXT_CHARACTER_LIMIT => $limit,
							]
						);
					default:
						$alias = $context[AliasesInLanguageValidator::CONTEXT_VALUE];
						throw new UseCaseError(
							UseCaseError::PATCHED_ALIAS_INVALID,
							"Changed alias for '{$aliasGroup->getLanguageCode()}' is invalid: '$alias'",
							[
								UseCaseError::CONTEXT_LANGUAGE => $aliasGroup->getLanguageCode(),
								UseCaseError::CONTEXT_VALUE => $alias,
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
					UseCaseError::PATCHED_ALIAS_INVALID_LANGUAGE_CODE,
					"Not a valid language code '$languageCode' in changed aliases",
					[ UseCaseError::CONTEXT_LANGUAGE => $languageCode ]
				);
			}
		}
	}
}
