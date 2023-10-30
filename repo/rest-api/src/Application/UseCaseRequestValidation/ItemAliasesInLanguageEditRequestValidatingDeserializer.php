<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCaseRequestValidation;

use LogicException;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\DuplicateAliasException;
use Wikibase\Repo\RestApi\Application\Serialization\EmptyAliasException;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\AliasesInLanguageValidator;

/**
 * @license GPL-2.0-or-later
 */
class ItemAliasesInLanguageEditRequestValidatingDeserializer {

	private AliasesInLanguageValidator $validator;
	private AliasesDeserializer $deserializer;

	public function __construct( AliasesInLanguageValidator $validator, AliasesDeserializer $deserializer ) {
		$this->validator = $validator;
		$this->deserializer = $deserializer;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( ItemAliasesInLanguageEditRequest $request ): array {
		$aliases = $request->getAliasesInLanguage();
		if ( empty( $aliases ) ) {
			throw new UseCaseError( UseCaseError::ALIAS_LIST_EMPTY, 'Alias list must not be empty' );
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
		} catch ( EmptyAliasException $e ) {
			throw new UseCaseError( UseCaseError::ALIAS_EMPTY, 'Alias must not be empty' );
		} catch ( DuplicateAliasException $e ) {
			$duplicateAlias = $e->getValue();
			throw new UseCaseError(
				UseCaseError::ALIAS_DUPLICATE,
				"Alias list contains a duplicate alias: '$duplicateAlias'",
				[ UseCaseError::CONTEXT_ALIAS => $duplicateAlias ]
			);
		}
	}

	private function validate( AliasGroup $aliasesInLanguage ): void {
		$validationError = $this->validator->validate( $aliasesInLanguage );

		if ( $validationError ) {
			$errorCode = $validationError->getCode();
			$context = $validationError->getContext();
			switch ( $errorCode ) {
				case AliasesInLanguageValidator::CODE_INVALID:
					throw new UseCaseError(
						UseCaseError::INVALID_ALIAS,
						"Not a valid alias: {$context[AliasesInLanguageValidator::CONTEXT_VALUE]}",
						[ UseCaseError::CONTEXT_ALIAS => $context[AliasesInLanguageValidator::CONTEXT_VALUE] ]
					);
				case AliasesInLanguageValidator::CODE_TOO_LONG:
					$limit = $context[AliasesInLanguageValidator::CONTEXT_LIMIT];
					throw new UseCaseError(
						UseCaseError::ALIAS_TOO_LONG,
						"Alias must be no more than $limit characters long",
						[
							UseCaseError::CONTEXT_VALUE => $context[AliasesInLanguageValidator::CONTEXT_VALUE],
							UseCaseError::CONTEXT_CHARACTER_LIMIT => $limit,
						]
					);
				default:
					throw new LogicException( "Unexpected validation error code: $errorCode" );
			}
		}
	}

}
