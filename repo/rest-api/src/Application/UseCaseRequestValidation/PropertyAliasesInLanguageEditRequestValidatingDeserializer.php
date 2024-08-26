<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCaseRequestValidation;

use LogicException;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidAliasesInLanguageException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\AliasesInLanguageValidator;

/**
 * @license GPL-2.0-or-later
 */
class PropertyAliasesInLanguageEditRequestValidatingDeserializer {

	private AliasesDeserializer $deserializer;
	private AliasesInLanguageValidator $validator;

	public function __construct( AliasesDeserializer $deserializer, AliasesInLanguageValidator $validator ) {
		$this->deserializer = $deserializer;
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( PropertyAliasesInLanguageEditRequest $request ): array {
		$aliases = $request->getAliasesInLanguage();
		if ( !$aliases ) {
			throw UseCaseError::newInvalidValue( '/aliases' );
		}

		$language = $request->getLanguageCode();
		$deserializedAliases = $this->deserialize( [ $language => $aliases ] );

		$aliasesInLanguage = $deserializedAliases->getByLanguage( $language );
		$this->validate( $aliasesInLanguage );

		return $aliasesInLanguage->getAliases();
	}

	private function deserialize( array $requestAliases ): AliasGroupList {
		try {
			return $this->deserializer->deserialize( $requestAliases );
		} catch ( InvalidAliasesInLanguageException $e ) {
			throw UseCaseError::newInvalidValue( '/aliases' );
		} catch ( InvalidFieldException $e ) {
			throw UseCaseError::newInvalidValue( "/aliases/{$e->getField()}" );
		}
	}

	private function validate( AliasGroup $aliasesInLanguage ): void {
		$validationError = $this->validator->validate( $aliasesInLanguage );
		if ( $validationError ) {
			$errorCode = $validationError->getCode();
			$context = $validationError->getContext();
			switch ( $errorCode ) {
				case AliasesInLanguageValidator::CODE_INVALID:
					$alias = $context[AliasesInLanguageValidator::CONTEXT_VALUE];
					$aliasIndex = Utils::getIndexOfValueInSerialization( $alias, $aliasesInLanguage->getAliases() );
					throw UseCaseError::newInvalidValue( "/aliases/$aliasIndex" );
				case AliasesInLanguageValidator::CODE_TOO_LONG:
					$alias = $context[AliasesInLanguageValidator::CONTEXT_VALUE];
					$aliasIndex = Utils::getIndexOfValueInSerialization( $alias, $aliasesInLanguage->getAliases() );
					throw UseCaseError::newValueTooLong( "/aliases/$aliasIndex", $context[AliasesInLanguageValidator::CONTEXT_LIMIT] );
				default:
					throw new LogicException( "Unexpected validation error code: $errorCode" );
			}
		}
	}

}
