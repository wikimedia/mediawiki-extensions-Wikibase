<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCaseRequestValidation;

use LogicException;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesInLanguageDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\AliasesInLanguageValidator;

/**
 * @license GPL-2.0-or-later
 */
class PropertyAliasesInLanguageEditRequestValidatingDeserializer {

	private AliasesInLanguageDeserializer $deserializer;
	private AliasesInLanguageValidator $validator;

	public function __construct( AliasesInLanguageDeserializer $deserializer, AliasesInLanguageValidator $validator ) {
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

		$aliasesInLanguage = new AliasGroup( $request->getLanguageCode(), $this->deserialize( $aliases ) );
		$this->validate( $aliasesInLanguage );

		return $aliasesInLanguage->getAliases();
	}

	private function deserialize( array $requestAliases ): array {
		try {
			return $this->deserializer->deserialize( $requestAliases, '/aliases' );
		} catch ( InvalidFieldException $e ) {
			throw UseCaseError::newInvalidValue( $e->getPath() );
		}
	}

	private function validate( AliasGroup $aliasesInLanguage ): void {
		$validationError = $this->validator->validate( $aliasesInLanguage, '/aliases' );
		if ( $validationError ) {
			$errorCode = $validationError->getCode();
			$context = $validationError->getContext();
			switch ( $errorCode ) {
				case AliasesInLanguageValidator::CODE_INVALID:
					throw UseCaseError::newInvalidValue( $context[AliasesInLanguageValidator::CONTEXT_PATH] );
				case AliasesInLanguageValidator::CODE_TOO_LONG:
					throw UseCaseError::newValueTooLong(
						$context[AliasesInLanguageValidator::CONTEXT_PATH],
						$context[AliasesInLanguageValidator::CONTEXT_LIMIT]
					);
				default:
					throw new LogicException( "Unexpected validation error code: $errorCode" );
			}
		}
	}

}
