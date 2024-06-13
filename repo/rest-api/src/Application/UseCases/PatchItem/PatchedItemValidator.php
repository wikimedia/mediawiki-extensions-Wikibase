<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItem;

use LogicException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinkDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementsDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\AliasesInLanguageValidator;
use Wikibase\Repo\RestApi\Application\Validation\AliasesValidator;
use Wikibase\Repo\RestApi\Application\Validation\DescriptionsSyntaxValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemDescriptionsContentsValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemDescriptionValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelsContentsValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\LabelsSyntaxValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

// disable because it forces comments for switch-cases that look like fall-throughs but aren't
// phpcs:disable PSR2.ControlStructures.SwitchDeclaration.TerminatingComment

/**
 * @license GPL-2.0-or-later
 */
class PatchedItemValidator {

	private LabelsSyntaxValidator $labelsSyntaxValidator;
	private ItemLabelsContentsValidator $labelsContentsValidator;
	private DescriptionsSyntaxValidator $descriptionsSyntaxValidator;
	private ItemDescriptionsContentsValidator $descriptionsContentsValidator;
	private AliasesValidator $aliasesValidator;
	private SitelinkDeserializer $sitelinkDeserializer;
	private StatementsDeserializer $statementsDeserializer;

	public function __construct(
		LabelsSyntaxValidator $labelsSyntaxValidator,
		ItemLabelsContentsValidator $labelsContentsValidator,
		DescriptionsSyntaxValidator $descriptionsSyntaxValidator,
		ItemDescriptionsContentsValidator $descriptionsContentsValidator,
		AliasesValidator $aliasesValidator,
		SitelinkDeserializer $sitelinkDeserializer,
		StatementsDeserializer $statementsDeserializer
	) {
		$this->labelsSyntaxValidator = $labelsSyntaxValidator;
		$this->labelsContentsValidator = $labelsContentsValidator;
		$this->descriptionsSyntaxValidator = $descriptionsSyntaxValidator;
		$this->descriptionsContentsValidator = $descriptionsContentsValidator;
		$this->aliasesValidator = $aliasesValidator;
		$this->sitelinkDeserializer = $sitelinkDeserializer;
		$this->statementsDeserializer = $statementsDeserializer;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( array $serialization, Item $originalItem ): Item {
		if ( !isset( $serialization['id'] ) ) { // ignore ID removal
			$serialization['id'] = $originalItem->getId()->getSerialization();
		}

		$this->assertNoIllegalModification( $serialization, $originalItem );
		$this->assertNoUnexpectedFields( $serialization );
		$this->assertValidFields( $serialization );
		$this->assertValidLabelsAndDescriptions( $serialization, $originalItem );
		$this->assertValidAliases( $serialization );

		return new Item(
			new ItemId( $serialization[ 'id' ] ),
			new Fingerprint(
				$this->labelsContentsValidator->getValidatedLabels(),
				$this->descriptionsContentsValidator->getValidatedDescriptions(),
				$this->aliasesValidator->getValidatedAliases()
			),
			$this->deserializeSitelinks( $serialization[ 'sitelinks' ] ?? [] ),
			$this->statementsDeserializer->deserialize( $serialization[ 'statements' ] ?? [] )
		);
	}

	private function assertNoIllegalModification( array $serialization, Item $originalItem ): void {
		if ( $serialization[ 'id' ] !== $originalItem->getId()->getSerialization() ) {
			throw new UseCaseError(
				UseCaseError::PATCHED_ITEM_INVALID_OPERATION_CHANGE_ITEM_ID,
				'Cannot change the ID of the existing item'
			);
		}
	}

	private function assertNoUnexpectedFields( array $serialization ): void {
		$expectedFields = [ 'id', 'type', 'labels', 'descriptions', 'aliases', 'sitelinks', 'statements' ];

		foreach ( array_keys( $serialization ) as $field ) {
			if ( !in_array( $field, $expectedFields ) ) {
				throw new UseCaseError(
					UseCaseError::PATCHED_ITEM_UNEXPECTED_FIELD,
					"The patched item contains an unexpected field: '$field'"
				);
			}
		}
	}

	private function assertValidFields( array $serialization ): void {
		// 'id' is not modifiable and 'type' is ignored, so we only check the expected array fields
		foreach ( [ 'labels', 'descriptions', 'aliases', 'sitelinks', 'statements' ] as $field ) {
			if ( isset( $serialization[$field] ) && !is_array( $serialization[$field] ) ) {
				$this->throwInvalidField( $field, $serialization[$field] );
			}
		}
	}

	private function assertValidLabelsAndDescriptions( array $serialization, Item $originalItem ): void {
		$labels = $serialization['labels'] ?? [];
		$descriptions = $serialization['descriptions'] ?? [];
		$validationError = $this->labelsSyntaxValidator->validate( $labels ) ??
						   $this->descriptionsSyntaxValidator->validate( $descriptions ) ??
						   $this->labelsContentsValidator->validate(
							   $this->labelsSyntaxValidator->getPartiallyValidatedLabels(),
							   $this->descriptionsSyntaxValidator->getPartiallyValidatedDescriptions(),
							   $this->getModifiedLanguages(
								   $originalItem->getLabels(),
								   $this->labelsSyntaxValidator->getPartiallyValidatedLabels()
							   )
						   ) ??
						   $this->descriptionsContentsValidator->validate(
							   $this->descriptionsSyntaxValidator->getPartiallyValidatedDescriptions(),
							   $this->labelsSyntaxValidator->getPartiallyValidatedLabels(),
							   $this->getModifiedLanguages(
								   $originalItem->getDescriptions(),
								   $this->descriptionsSyntaxValidator->getPartiallyValidatedDescriptions()
							   )
						   );

		if ( $validationError ) {
			$this->handleLanguageCodeValidationError( $validationError );
			$this->handleLabelsValidationError( $validationError, $labels );
			$this->handleDescriptionsValidationError( $validationError, $descriptions );
			throw new LogicException( "Unknown validation error: {$validationError->getCode()}" );
		}
	}

	private function handleLanguageCodeValidationError( ValidationError $validationError ): void {
		if ( $validationError->getCode() !== LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE ) {
			return;
		}

		$context = $validationError->getContext();
		$languageCode = $context[LanguageCodeValidator::CONTEXT_LANGUAGE_CODE];
		switch ( $context[LanguageCodeValidator::CONTEXT_PATH] ) {
			case 'labels':
				throw new UseCaseError(
					UseCaseError::PATCHED_LABEL_INVALID_LANGUAGE_CODE,
					"Not a valid language code '$languageCode' in changed labels",
					[ UseCaseError::CONTEXT_LANGUAGE => $languageCode ]
				);
			case 'descriptions':
				throw new UseCaseError(
					UseCaseError::PATCHED_DESCRIPTION_INVALID_LANGUAGE_CODE,
					"Not a valid language code '$languageCode' in changed descriptions",
					[ UseCaseError::CONTEXT_LANGUAGE => $languageCode ]
				);
		}
	}

	private function handleLabelsValidationError( ValidationError $validationError, array $labelsSerialization ): void {
		$context = $validationError->getContext();

		switch ( $validationError->getCode() ) {
			case LabelsSyntaxValidator::CODE_LABELS_NOT_ASSOCIATIVE:
				$this->throwInvalidField( 'labels', $labelsSerialization );
			case LabelsSyntaxValidator::CODE_EMPTY_LABEL:
				$languageCode = $validationError->getContext()[LabelsSyntaxValidator::CONTEXT_LANGUAGE];
				throw new UseCaseError(
					UseCaseError::PATCHED_LABEL_EMPTY,
					"Changed label for '$languageCode' cannot be empty",
					[ UseCaseError::CONTEXT_LANGUAGE => $languageCode ]
				);
			case LabelsSyntaxValidator::CODE_INVALID_LABEL_TYPE:
				$language = $context[LabelsSyntaxValidator::CONTEXT_LANGUAGE];
				$value = json_encode( $context[LabelsSyntaxValidator::CONTEXT_LABEL] );
				throw new UseCaseError(
					UseCaseError::PATCHED_LABEL_INVALID,
					"Changed label for '{$language}' is invalid: {$value}",
					[ UseCaseError::CONTEXT_LANGUAGE => $language, UseCaseError::CONTEXT_VALUE => $value ]
				);
			case ItemLabelValidator::CODE_INVALID:
				$language = $context[ItemLabelValidator::CONTEXT_LANGUAGE];
				$value = $context[ItemLabelValidator::CONTEXT_LABEL];
				throw new UseCaseError(
					UseCaseError::PATCHED_LABEL_INVALID,
					"Changed label for '{$language}' is invalid: {$value}",
					[ UseCaseError::CONTEXT_LANGUAGE => $language, UseCaseError::CONTEXT_VALUE => $value ]
				);
			case ItemLabelValidator::CODE_TOO_LONG:
				$maxLabelLength = $context[ItemLabelValidator::CONTEXT_LIMIT];
				$language = $context[ItemLabelValidator::CONTEXT_LANGUAGE];
				throw new UseCaseError(
					UseCaseError::PATCHED_LABEL_TOO_LONG,
					"Changed label for '{$language}' must not be more than $maxLabelLength characters long",
					[
						UseCaseError::CONTEXT_LANGUAGE => $context[ItemLabelValidator::CONTEXT_LANGUAGE],
						UseCaseError::CONTEXT_VALUE => $context[ItemLabelValidator::CONTEXT_LABEL],
						UseCaseError::CONTEXT_CHARACTER_LIMIT => $context[ItemLabelValidator::CONTEXT_LIMIT],
					]
				);
			case ItemLabelValidator::CODE_LABEL_DESCRIPTION_DUPLICATE:
				throw new UseCaseError(
					UseCaseError::PATCHED_ITEM_LABEL_DESCRIPTION_DUPLICATE,
					"Item '{$context[ItemLabelValidator::CONTEXT_MATCHING_ITEM_ID]}' already has label " .
					"'{$context[ItemLabelValidator::CONTEXT_LABEL]}' associated with language code " .
					"'{$context[ItemLabelValidator::CONTEXT_LANGUAGE]}', using the same description text",
					[
						UseCaseError::CONTEXT_LANGUAGE => $context[ItemLabelValidator::CONTEXT_LANGUAGE],
						UseCaseError::CONTEXT_LABEL => $context[ItemLabelValidator::CONTEXT_LABEL],
						UseCaseError::CONTEXT_DESCRIPTION => $context[ItemLabelValidator::CONTEXT_DESCRIPTION],
						UseCaseError::CONTEXT_MATCHING_ITEM_ID => $context[ItemLabelValidator::CONTEXT_MATCHING_ITEM_ID],
					]
				);
			case ItemLabelValidator::CODE_LABEL_SAME_AS_DESCRIPTION:
				$language = $context[ItemLabelValidator::CONTEXT_LANGUAGE];
				throw new UseCaseError(
					UseCaseError::PATCHED_ITEM_LABEL_DESCRIPTION_SAME_VALUE,
					"Label and description for language code '{$language}' can not have the same value",
					[ UseCaseError::CONTEXT_LANGUAGE => $context[ItemLabelValidator::CONTEXT_LANGUAGE] ]
				);
		}
	}

	private function handleDescriptionsValidationError( ValidationError $validationError, array $descriptionsSerialization ): void {
		$context = $validationError->getContext();

		switch ( $validationError->getCode() ) {
			case DescriptionsSyntaxValidator::CODE_DESCRIPTIONS_NOT_ASSOCIATIVE:
				$this->throwInvalidField( 'descriptions', $descriptionsSerialization );
			case DescriptionsSyntaxValidator::CODE_EMPTY_DESCRIPTION:
				$languageCode = $validationError->getContext()[DescriptionsSyntaxValidator::CONTEXT_LANGUAGE];
				throw new UseCaseError(
					UseCaseError::PATCHED_DESCRIPTION_EMPTY,
					"Changed description for '$languageCode' cannot be empty",
					[ UseCaseError::CONTEXT_LANGUAGE => $languageCode ]
				);
			case DescriptionsSyntaxValidator::CODE_INVALID_DESCRIPTION_TYPE:
				$language = $context[DescriptionsSyntaxValidator::CONTEXT_LANGUAGE];
				$value = json_encode( $context[DescriptionsSyntaxValidator::CONTEXT_DESCRIPTION] );
				throw new UseCaseError(
					UseCaseError::PATCHED_DESCRIPTION_INVALID,
					"Changed description for '{$language}' is invalid: {$value}",
					[ UseCaseError::CONTEXT_LANGUAGE => $language, UseCaseError::CONTEXT_VALUE => $value ]
				);
			case ItemDescriptionValidator::CODE_INVALID:
				$language = $context[ItemDescriptionValidator::CONTEXT_LANGUAGE];
				$value = $context[ItemDescriptionValidator::CONTEXT_DESCRIPTION];
				throw new UseCaseError(
					UseCaseError::PATCHED_DESCRIPTION_INVALID,
					"Changed description for '{$language}' is invalid: {$value}",
					[ UseCaseError::CONTEXT_LANGUAGE => $language, UseCaseError::CONTEXT_VALUE => $value ]
				);
			case ItemDescriptionValidator::CODE_TOO_LONG:
				$languageCode = $context[ItemDescriptionValidator::CONTEXT_LANGUAGE];
				$maxDescriptionLength = $context[ItemDescriptionValidator::CONTEXT_LIMIT];
				throw new UseCaseError(
					UseCaseError::PATCHED_DESCRIPTION_TOO_LONG,
					"Changed description for '$languageCode' must not be more than $maxDescriptionLength characters long",
					[
						UseCaseError::CONTEXT_LANGUAGE => $languageCode,
						UseCaseError::CONTEXT_VALUE => $context[ItemDescriptionValidator::CONTEXT_DESCRIPTION],
						UseCaseError::CONTEXT_CHARACTER_LIMIT => $context[ItemDescriptionValidator::CONTEXT_LIMIT],
					]
				);
			case ItemDescriptionValidator::CODE_DESCRIPTION_SAME_AS_LABEL:
				$language = $context[ItemDescriptionValidator::CONTEXT_LANGUAGE];
				throw new UseCaseError(
					UseCaseError::PATCHED_ITEM_LABEL_DESCRIPTION_SAME_VALUE,
					"Label and description for language code '{$language}' can not have the same value",
					[ UseCaseError::CONTEXT_LANGUAGE => $context[ItemDescriptionValidator::CONTEXT_LANGUAGE] ]
				);
			case ItemDescriptionValidator::CODE_DESCRIPTION_LABEL_DUPLICATE:
				throw new UseCaseError(
					UseCaseError::PATCHED_ITEM_LABEL_DESCRIPTION_DUPLICATE,
					"Item '{$context[ItemDescriptionValidator::CONTEXT_MATCHING_ITEM_ID]}' already has label " .
					"'{$context[ItemDescriptionValidator::CONTEXT_LABEL]}' associated with language code " .
					"'{$context[ItemDescriptionValidator::CONTEXT_LANGUAGE]}', using the same description text",
					[
						UseCaseError::CONTEXT_LANGUAGE => $context[ItemDescriptionValidator::CONTEXT_LANGUAGE],
						UseCaseError::CONTEXT_LABEL => $context[ItemDescriptionValidator::CONTEXT_LABEL],
						UseCaseError::CONTEXT_DESCRIPTION => $context[ItemDescriptionValidator::CONTEXT_DESCRIPTION],
						UseCaseError::CONTEXT_MATCHING_ITEM_ID => $context[ItemDescriptionValidator::CONTEXT_MATCHING_ITEM_ID],
					]
				);
		}
	}

	private function assertValidAliases( array $serialization ): void {
		$aliasesSerialization = $serialization[ 'aliases' ] ?? [];
		$validationError = $this->aliasesValidator->validate( $aliasesSerialization );
		if ( $validationError ) {
			$context = $validationError->getContext();
			switch ( $validationError->getCode() ) {
				case AliasesValidator::CODE_INVALID_ALIASES:
					$this->throwInvalidField( 'aliases', $aliasesSerialization );
				case LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE:
					$language = $context[LanguageCodeValidator::CONTEXT_LANGUAGE_CODE];
					throw new UseCaseError(
						UseCaseError::PATCHED_ALIASES_INVALID_LANGUAGE_CODE,
						"Not a valid language code '$language' in changed aliases",
						[ UseCaseError::CONTEXT_LANGUAGE => $language ]
					);
				case AliasesValidator::CODE_EMPTY_ALIAS:
					$language = $context[AliasesValidator::CONTEXT_LANGUAGE];
					throw new UseCaseError(
						UseCaseError::PATCHED_ALIAS_EMPTY,
						"Changed alias for '$language' cannot be empty",
						[ UseCaseError::CONTEXT_LANGUAGE => $language ]
					);
				case AliasesValidator::CODE_DUPLICATE_ALIAS:
					$language = $context[AliasesValidator::CONTEXT_LANGUAGE];
					$value = $context[AliasesValidator::CONTEXT_ALIAS];
					throw new UseCaseError(
						UseCaseError::PATCHED_ALIAS_DUPLICATE,
						"Aliases in language '$language' contain duplicate alias: '$value'",
						[ UseCaseError::CONTEXT_LANGUAGE => $language, UseCaseError::CONTEXT_VALUE => $value ]
					);
				case AliasesValidator::CODE_INVALID_ALIAS:
					$language = $context[AliasesValidator::CONTEXT_LANGUAGE];
					$value = $context[AliasesValidator::CONTEXT_ALIAS];
					throw new UseCaseError(
						UseCaseError::PATCHED_ALIASES_INVALID_FIELD,
						"Patched value for '$language' is invalid",
						[ UseCaseError::CONTEXT_PATH => $language, UseCaseError::CONTEXT_VALUE => $value ]
					);
				case AliasesInLanguageValidator::CODE_TOO_LONG:
					$limit = $context[AliasesInLanguageValidator::CONTEXT_LIMIT];
					$language = $context[AliasesInLanguageValidator::CONTEXT_LANGUAGE];
					throw new UseCaseError(
						UseCaseError::PATCHED_ALIAS_TOO_LONG,
						"Changed alias for '$language' must not be more than $limit characters long",
						[
							UseCaseError::CONTEXT_LANGUAGE => $language,
							UseCaseError::CONTEXT_VALUE => $context[AliasesInLanguageValidator::CONTEXT_VALUE],
							UseCaseError::CONTEXT_CHARACTER_LIMIT => $limit,
						]
					);
				default:
					throw new UseCaseError(
						UseCaseError::PATCHED_ALIASES_INVALID_FIELD,
						"Patched value for '{$context[AliasesInLanguageValidator::CONTEXT_LANGUAGE]}' is invalid",
						[
							UseCaseError::CONTEXT_PATH => $context[AliasesInLanguageValidator::CONTEXT_PATH],
							UseCaseError::CONTEXT_VALUE => $context[AliasesInLanguageValidator::CONTEXT_VALUE],
						]
					);
			}
		}
	}

	private function getModifiedLanguages( TermList $original, TermList $modified ): array {
		return array_keys( array_filter(
			iterator_to_array( $modified ),
			fn( Term $term ) => !$original->hasTermForLanguage( $term->getLanguageCode() ) ||
								!$original->getByLanguage( $term->getLanguageCode() )->equals( $term )
		) );
	}

	private function deserializeSitelinks( array $sitelinksSerialization ): SiteLinkList {
		$sitelinkList = [];
		foreach ( $sitelinksSerialization as $siteId => $sitelink ) {
			$sitelinkList[] = $this->sitelinkDeserializer->deserialize( $siteId, $sitelink );
		}

		return new SiteLinkList( $sitelinkList );
	}

	/**
	 * @param string $field
	 * @param mixed $value
	 *
	 * @return never
	 */
	private function throwInvalidField( string $field, $value ): void {
		throw new UseCaseError(
			UseCaseError::PATCHED_ITEM_INVALID_FIELD,
			"Invalid input for '$field' in the patched item",
			[
				UseCaseError::CONTEXT_PATH => $field,
				UseCaseError::CONTEXT_VALUE => $value,
			]
		);
	}

}
