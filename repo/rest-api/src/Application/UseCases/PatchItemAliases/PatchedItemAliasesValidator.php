<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItemAliases;

use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\Utils;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\AliasesInLanguageValidator;
use Wikibase\Repo\RestApi\Application\Validation\AliasLanguageCodeValidator;

/**
 * @license GPL-2.0-or-later
 */
class PatchedItemAliasesValidator {

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
			throw UseCaseError::newPatchResultInvalidValue( '', $serialization );
		}

		$this->validateLanguageCodes( array_keys( $serialization ) );

		$aliases = $this->deserialize( $serialization );

		$this->validateAliases( $aliases );

		return $aliases;
	}

	private function deserialize( array $serialization ): AliasGroupList {
		try {
			return $this->aliasesDeserializer->deserialize( $serialization );
		} catch ( InvalidFieldException $e ) {
			throw UseCaseError::newPatchResultInvalidValue( "/{$e->getPath()}", $e->getValue() );
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
						throw UseCaseError::newPatchResultInvalidValue( "/$path", $value );
				}
			}
		}
	}

	private function validateLanguageCodes( array $languageCodes ): void {
		foreach ( $languageCodes as $languageCode ) {
			// need to cast $languageCode to a string because, while json object keys are always strings,
			// php converts numeric looking keys to integers
			if ( $this->languageCodeValidator->validate( (string)$languageCode ) ) {
				throw UseCaseError::newPatchResultInvalidKey( '', (string)$languageCode );
			}
		}
	}

}
