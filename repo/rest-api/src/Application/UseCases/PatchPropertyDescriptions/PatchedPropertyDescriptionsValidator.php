<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyDescriptions;

use LogicException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\EmptyDescriptionException;
use Wikibase\Repo\RestApi\Application\Serialization\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyDescriptionValidator;

/**
 * @license GPL-2.0-or-later
 */
class PatchedPropertyDescriptionsValidator {

	private DescriptionsDeserializer $descriptionsDeserializer;
	private PropertyDescriptionValidator $descriptionValidator;
	private LanguageCodeValidator $languageCodeValidator;

	public function __construct(
		DescriptionsDeserializer $descriptionsDeserializer,
		PropertyDescriptionValidator $descriptionValidator,
		LanguageCodeValidator $languageCodeValidator
	) {
		$this->descriptionsDeserializer = $descriptionsDeserializer;
		$this->descriptionValidator = $descriptionValidator;
		$this->languageCodeValidator = $languageCodeValidator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize(
		PropertyId $propertyId,
		TermList $originalDescriptions,
		array $patchedSerialization
	): TermList {
		$patchedDescriptions = $this->deserializeDescriptions( $patchedSerialization );
		foreach ( $this->getModifiedDescriptions( $originalDescriptions, $patchedDescriptions ) as $description ) {
			$this->validateLanguageCode( $description );
			$this->validateDescription( $propertyId, $description );
		}

		return $patchedDescriptions;
	}

	private function deserializeDescriptions( array $serialization ): TermList {
		try {
			return $this->descriptionsDeserializer->deserialize( $serialization );
		} catch ( EmptyDescriptionException $e ) {
			$languageCode = $e->getField();
			throw new UseCaseError(
				UseCaseError::PATCHED_DESCRIPTION_EMPTY,
				"Changed description for '$languageCode' cannot be empty",
				[ UseCaseError::CONTEXT_LANGUAGE => $languageCode ]
			);
		} catch ( InvalidFieldException $e ) {
			$languageCode = $e->getField();
			$invalidDescription = json_encode( $e->getValue() );
			throw new UseCaseError(
				UseCaseError::PATCHED_DESCRIPTION_INVALID,
				"Changed description for '$languageCode' is invalid: $invalidDescription",
				[
					UseCaseError::CONTEXT_LANGUAGE => $languageCode,
					UseCaseError::CONTEXT_VALUE => $invalidDescription,
				]
			);
		}
	}

	private function getModifiedDescriptions( TermList $original, TermList $modified ): array {
		return array_filter(
			iterator_to_array( $modified ),
			fn( Term $description ) => !$original->hasTermForLanguage( $description->getLanguageCode() ) ||
				!$original->getByLanguage( $description->getLanguageCode() )->equals( $description )
		);
	}

	private function validateDescription( PropertyId $propertyId, Term $description ): void {
		$validationError = $this->descriptionValidator->validate( $propertyId, $description->getLanguageCode(), $description->getText() );
		if ( !$validationError ) {
			return;
		}

		$context = $validationError->getContext();
		switch ( $validationError->getCode() ) {
			case PropertyDescriptionValidator::CODE_INVALID:
				throw new UseCaseError(
					UseCaseError::PATCHED_DESCRIPTION_INVALID,
					"Changed description for '{$description->getLanguageCode()}' is invalid: {$description->getText()}",
					[
						UseCaseError::CONTEXT_LANGUAGE => $description->getLanguageCode(),
						UseCaseError::CONTEXT_VALUE => $description->getText(),
					]
				);
			case PropertyDescriptionValidator::CODE_TOO_LONG:
				$limit = $context[PropertyDescriptionValidator::CONTEXT_LIMIT];
				throw new UseCaseError(
					UseCaseError::PATCHED_DESCRIPTION_TOO_LONG,

					"Changed description for '{$description->getLanguageCode()}' must not be more than $limit characters long",
					[
						UseCaseError::CONTEXT_LANGUAGE => $description->getLanguageCode(),
						UseCaseError::CONTEXT_VALUE => $context[PropertyDescriptionValidator::CONTEXT_DESCRIPTION],
						UseCaseError::CONTEXT_CHARACTER_LIMIT => $context[PropertyDescriptionValidator::CONTEXT_LIMIT],
					]
				);
			case PropertyDescriptionValidator::CODE_LABEL_DESCRIPTION_EQUAL:
				$language = $context[PropertyDescriptionValidator::CONTEXT_LANGUAGE];
				throw new UseCaseError(
					UseCaseError::PATCHED_PROPERTY_LABEL_DESCRIPTION_SAME_VALUE,
					"Label and description for language code {$language} can not have the same value.",
					[ UseCaseError::CONTEXT_LANGUAGE => $context[PropertyDescriptionValidator::CONTEXT_LANGUAGE] ]
				);
			default:
				throw new LogicException( "Unknown validation error: {$validationError->getCode()}" );
		}
	}

	private function validateLanguageCode( Term $description ): void {
		$validationError = $this->languageCodeValidator->validate( $description->getLanguageCode() );
		if ( $validationError ) {
			$languageCode = $validationError->getContext()[LanguageCodeValidator::CONTEXT_LANGUAGE_CODE_VALUE];
			throw new UseCaseError(
				UseCaseError::PATCHED_DESCRIPTION_INVALID_LANGUAGE_CODE,
				"Not a valid language code '$languageCode' in changed descriptions",
				[ UseCaseError::CONTEXT_LANGUAGE => $languageCode ]
			);
		}
	}

}
