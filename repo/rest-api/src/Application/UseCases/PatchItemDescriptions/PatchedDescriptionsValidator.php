<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItemDescriptions;

use LogicException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\EmptyDescriptionException;
use Wikibase\Repo\RestApi\Application\Serialization\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\ItemDescriptionValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;

/**
 * @license GPL-2.0-or-later
 */
class PatchedDescriptionsValidator {

	private DescriptionsDeserializer $descriptionsDeserializer;
	private ItemDescriptionValidator $descriptionValidator;
	private LanguageCodeValidator $languageCodeValidator;

	public function __construct(
		DescriptionsDeserializer $descriptionsDeserializer,
		ItemDescriptionValidator $descriptionValidator,
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
		ItemId $itemId,
		TermList $originalDescriptions,
		array $descriptionsSerialization
	): TermList {
		$patchedDescriptions = $this->deserializeDescriptions( $descriptionsSerialization );
		foreach ( $this->getModifiedDescriptions( $originalDescriptions, $patchedDescriptions ) as $description ) {
			$this->validateDescription( $itemId, $description );
			$this->validateLanguageCode( $description );
		}

		return $patchedDescriptions;
	}

	private function deserializeDescriptions( array $descriptionsSerialization ): TermList {
		try {
			$descriptions = $this->descriptionsDeserializer->deserialize( $descriptionsSerialization );
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

		return $descriptions;
	}

	private function getModifiedDescriptions( TermList $original, TermList $modified ): array {
		return array_filter(
			iterator_to_array( $modified ),
			fn( Term $description ) => !$original->hasTermForLanguage( $description->getLanguageCode() ) ||
				!$original->getByLanguage( $description->getLanguageCode() )->equals( $description )
		);
	}

	private function validateDescription( ItemId $itemId, Term $description ): void {
		$validationError = $this->descriptionValidator->validate(
			$itemId,
			$description->getLanguageCode(),
			$description->getText()
		);
		if ( !$validationError ) {
			return;
		}

		$context = $validationError->getContext();
		switch ( $validationError->getCode() ) {
			case ItemDescriptionValidator::CODE_INVALID:
				$descriptionText = $context[ ItemDescriptionValidator::CONTEXT_DESCRIPTION ];
				throw new UseCaseError(
					UseCaseError::PATCHED_DESCRIPTION_INVALID,
					"Changed description for '{$description->getLanguageCode()}' is invalid: $descriptionText",
					[
						UseCaseError::CONTEXT_LANGUAGE => $description->getLanguageCode(),
						UseCaseError::CONTEXT_VALUE => $descriptionText,
					]
				);
			case ItemDescriptionValidator::CODE_TOO_LONG:
				$languageCode = $description->getLanguageCode();
				$maxDescriptionLength = $context[ ItemDescriptionValidator::CONTEXT_LIMIT ];
				throw new UseCaseError(
					UseCaseError::PATCHED_DESCRIPTION_TOO_LONG,
					"Changed description for '$languageCode' must not be more than $maxDescriptionLength characters long",
					[
						UseCaseError::CONTEXT_LANGUAGE => $languageCode,
						UseCaseError::CONTEXT_VALUE => $context[ ItemDescriptionValidator::CONTEXT_DESCRIPTION ],
						UseCaseError::CONTEXT_CHARACTER_LIMIT => $context[ ItemDescriptionValidator::CONTEXT_LIMIT ],
					]
				);
			case ItemDescriptionValidator::CODE_LABEL_DESCRIPTION_DUPLICATE:
				$languageCode = $context[ ItemDescriptionValidator::CONTEXT_LANGUAGE ];
				$description = $context[ ItemDescriptionValidator::CONTEXT_DESCRIPTION ];
				$duplicateItemId = $context[ ItemDescriptionValidator::CONTEXT_MATCHING_ITEM_ID ];
				throw new UseCaseError(
					UseCaseError::PATCHED_ITEM_LABEL_DESCRIPTION_DUPLICATE,
					"Item $duplicateItemId already has description '$description' associated with " .
					"language code $languageCode, using the same label.",
					[
						UseCaseError::CONTEXT_LANGUAGE => $context[ ItemDescriptionValidator::CONTEXT_LANGUAGE ],
						UseCaseError::CONTEXT_LABEL => $context[ ItemDescriptionValidator::CONTEXT_LABEL ],
						UseCaseError::CONTEXT_DESCRIPTION => $context[ ItemDescriptionValidator::CONTEXT_DESCRIPTION ],
						UseCaseError::CONTEXT_MATCHING_ITEM_ID => $context[ ItemDescriptionValidator::CONTEXT_MATCHING_ITEM_ID ],
					]
				);
			case ItemDescriptionValidator::CODE_LABEL_DESCRIPTION_EQUAL:
				$language = $context[ ItemDescriptionValidator::CONTEXT_LANGUAGE ];
				throw new UseCaseError(
					UseCaseError::PATCHED_ITEM_LABEL_DESCRIPTION_SAME_VALUE,
					"Label and description for language code {$language} can not have the same value.",
					[ UseCaseError::CONTEXT_LANGUAGE => $context[ ItemDescriptionValidator::CONTEXT_LANGUAGE ] ]
				);
			default:
				throw new LogicException( "Unknown validation error: {$validationError->getCode()}" );
		}
	}

	private function validateLanguageCode( Term $description ): void {
		$validationError = $this->languageCodeValidator->validate( $description->getLanguageCode() );
		if ( $validationError ) {
			$languageCode = $validationError->getContext()[ LanguageCodeValidator::CONTEXT_LANGUAGE_CODE_VALUE ];
			throw new UseCaseError(
				UseCaseError::PATCHED_DESCRIPTION_INVALID_LANGUAGE_CODE,
				"Not a valid language code '$languageCode' in changed descriptions",
				[ UseCaseError::CONTEXT_LANGUAGE => $languageCode ]
			);
		}
	}

}
