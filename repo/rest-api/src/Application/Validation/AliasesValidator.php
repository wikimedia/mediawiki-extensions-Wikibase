<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use LogicException;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidFieldException;

/**
 * @license GPL-2.0-or-later
 */
class AliasesValidator {
	public const CODE_INVALID_VALUE = 'aliases-validator-code-invalid-value';
	public const CODE_INVALID_ALIASES = 'aliases-validator-code-invalid-aliases';
	public const CODE_INVALID_ALIAS_LIST = 'aliases-validator-code-invalid-alias-list';

	public const CONTEXT_VALUE = 'aliases-validator-context-value';
	public const CONTEXT_ALIASES = 'aliases-validator-context-aliases';
	public const CONTEXT_LANGUAGE = 'aliases-validator-context-language';
	public const CONTEXT_PATH = 'aliases-validator-context-path';

	private AliasesInLanguageValidator $aliasesInLanguageValidator;
	private AliasLanguageCodeValidator $languageCodeValidator;
	private AliasesDeserializer $aliasesDeserializer;

	private ?AliasGroupList $deserializedAliases = null;

	public function __construct(
		AliasesInLanguageValidator $aliasesInLanguageValidator,
		AliasLanguageCodeValidator $languageCodeValidator,
		AliasesDeserializer $aliasesDeserializer
	) {
		$this->aliasesInLanguageValidator = $aliasesInLanguageValidator;
		$this->aliasesDeserializer = $aliasesDeserializer;
		$this->languageCodeValidator = $languageCodeValidator;
	}

	public function validate( array $aliases, string $basePath ): ?ValidationError {
		if ( count( $aliases ) === 0 ) {
			$this->deserializedAliases = new AliasGroupList();
			return null;
		}
		if ( array_is_list( $aliases ) ) {
			return new ValidationError( self::CODE_INVALID_ALIASES, [ self::CONTEXT_ALIASES => $aliases ] );
		}

		foreach ( $aliases as $languageCode => $aliasesInLanguage ) {
			$languageValidationError = $this->languageCodeValidator->validate( (string)$languageCode );
			if ( $languageValidationError ) {
				return new ValidationError(
					LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE,
					array_merge(
						$languageValidationError->getContext(),
						[ LanguageCodeValidator::CONTEXT_FIELD => 'aliases' ]
					)
				);
			}

			// @phan-suppress-next-line PhanRedundantConditionInLoop
			if ( !is_array( $aliasesInLanguage ) ) {
				return new ValidationError(
					self::CODE_INVALID_ALIAS_LIST,
					[ self::CONTEXT_LANGUAGE => $languageCode ]
				);
			}
		}

		return $this->deserializeAliases( $aliases, $basePath ) ?? $this->validateAliases( $this->deserializedAliases );
	}

	private function deserializeAliases( array $aliases, string $basePath ): ?ValidationError {
		try {
			$this->deserializedAliases = $this->aliasesDeserializer->deserialize( $aliases, $basePath );
		} catch ( InvalidFieldException $e ) {
			return new ValidationError(
				self::CODE_INVALID_VALUE,
				[ self::CONTEXT_PATH => $e->getPath(), self::CONTEXT_VALUE => $e->getValue() ]
			);
		}

		return null;
	}

	private function validateAliases( AliasGroupList $aliases ): ?ValidationError {
		foreach ( $aliases as $aliasesInLanguage ) {
			$aliasesInLanguageValidationError = $this->aliasesInLanguageValidator->validate( $aliasesInLanguage );
			if ( $aliasesInLanguageValidationError ) {
				return $aliasesInLanguageValidationError;
			}
		}

		return null;
	}

	public function getValidatedAliases(): AliasGroupList {
		if ( $this->deserializedAliases === null ) {
			throw new LogicException( 'getValidatedAliases() called before validate()' );
		}

		return $this->deserializedAliases;
	}

}
