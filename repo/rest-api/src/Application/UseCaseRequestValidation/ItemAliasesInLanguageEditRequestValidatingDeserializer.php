<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCaseRequestValidation;

use LogicException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\DuplicateAliasException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\EmptyAliasException;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\AliasesInLanguageValidator;
use Wikibase\Repo\RestApi\Domain\Services\ItemAliasesInLanguageRetriever;

/**
 * @license GPL-2.0-or-later
 */
class ItemAliasesInLanguageEditRequestValidatingDeserializer {

	private AliasesDeserializer $deserializer;
	private AliasesInLanguageValidator $validator;
	private ItemAliasesInLanguageRetriever $aliasesRetriever;

	public function __construct(
		AliasesDeserializer $deserializer,
		AliasesInLanguageValidator $validator,
		ItemAliasesInLanguageRetriever $aliasesRetriever
	) {
		$this->deserializer = $deserializer;
		$this->validator = $validator;
		$this->aliasesRetriever = $aliasesRetriever;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( ItemAliasesInLanguageEditRequest $request ): array {
		$aliases = $request->getAliasesInLanguage();
		if ( !$aliases ) {
			throw UseCaseError::newInvalidValue( '/aliases' );
		}

		$language = $request->getLanguageCode();
		$deserializedAliases = $this->deserialize( [ $language => $aliases ] );

		$aliasesInLanguage = $deserializedAliases->getByLanguage( $language );
		$this->validate( $aliasesInLanguage );

		$this->checkForDuplicatesWithExistingAliases( new ItemId( $request->getItemId() ), $aliasesInLanguage );

		return $aliasesInLanguage->getAliases();
	}

	private function deserialize( array $requestAliases ): AliasGroupList {
		try {
			return $this->deserializer->deserialize( $requestAliases );
		} catch ( EmptyAliasException $e ) {
			throw UseCaseError::newInvalidValue( "/aliases/{$e->getIndex()}" );
		} catch ( DuplicateAliasException $e ) {
			$this->throwDuplicateAliasError( $e->getValue() );
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

	private function checkForDuplicatesWithExistingAliases( ItemId $itemId, AliasGroup $newAliases ): void {
		$existingAliases = $this->aliasesRetriever->getAliasesInLanguage( $itemId, $newAliases->getLanguageCode() );
		if ( !$existingAliases ) {
			return;
		}

		$duplicates = array_intersect( $newAliases->getAliases(), $existingAliases->getAliases() );
		if ( $duplicates ) {
			$this->throwDuplicateAliasError( $duplicates[0] );
		}
	}

	/**
	 * @throws UseCaseError
	 * @return never
	 */
	private function throwDuplicateAliasError( string $duplicateAlias ): void {
		throw new UseCaseError(
			UseCaseError::ALIAS_DUPLICATE,
			"Alias list contains a duplicate alias: '$duplicateAlias'",
			[ UseCaseError::CONTEXT_ALIAS => $duplicateAlias ]
		);
	}

}
