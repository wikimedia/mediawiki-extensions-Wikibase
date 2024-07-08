<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCaseRequestValidation;

use LogicException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\AliasesInLanguageValidator;
use Wikibase\Repo\RestApi\Application\Validation\AliasesValidator;
use Wikibase\Repo\RestApi\Application\Validation\DescriptionsSyntaxValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemDescriptionValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemValidator;
use Wikibase\Repo\RestApi\Application\Validation\LabelsSyntaxValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\SiteIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\SitelinksValidator;
use Wikibase\Repo\RestApi\Application\Validation\SitelinkValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementsValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

// disable because it forces comments for switch-cases that look like fall-throughs but aren't
// phpcs:disable PSR2.ControlStructures.SwitchDeclaration.TerminatingComment

/**
 * @license GPL-2.0-or-later
 */
class ItemSerializationRequestValidatingDeserializer {

	private ItemValidator $validator;

	public function __construct( ItemValidator $validator ) {
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( ItemSerializationRequest $request ): Item {
		$itemSerialization = $request->getItem();
		$validationError = $this->validator->validate( $itemSerialization );

		if ( $validationError ) {
			$context = $validationError->getContext();
			switch ( $validationError->getCode() ) {
				case ItemValidator::CODE_INVALID_FIELD:
					$field = $context[ItemValidator::CONTEXT_FIELD];
					$this->throwInvalidField( $field, $field, $context[ItemValidator::CONTEXT_VALUE] );
				case ItemValidator::CODE_UNEXPECTED_FIELD:
					throw new UseCaseError(
						UseCaseError::ITEM_DATA_UNEXPECTED_FIELD,
						'The request body contains an unexpected field',
						[ UseCaseError::CONTEXT_FIELD => $context[ItemValidator::CONTEXT_FIELD] ]
					);
				case LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE:
					throw new UseCaseError(
						UseCaseError::INVALID_LANGUAGE_CODE,
						"Not a valid language code: {$context[LanguageCodeValidator::CONTEXT_LANGUAGE_CODE]}",
						[
							UseCaseError::CONTEXT_PATH => $context[LanguageCodeValidator::CONTEXT_PATH],
							UseCaseError::CONTEXT_LANGUAGE => $context[LanguageCodeValidator::CONTEXT_LANGUAGE_CODE],
						]
					);
			}

			$this->handleLabelValidationErrors( $validationError, $itemSerialization['labels'] ?? [] );
			$this->handleDescriptionValidationErrors( $validationError, $itemSerialization['descriptions'] ?? [] );
			$this->handleAliasesValidationErrors( $validationError, $itemSerialization['aliases'] ?? [] );
			$this->handleStatementsValidationErrors( $validationError );
			$this->handleSitelinksValidationErrors( $validationError, $itemSerialization['sitelinks'] ?? [] );

			throw new LogicException( "Unknown validation error code: {$validationError->getCode()}" );
		}

		return $this->validator->getValidatedItem();
	}

	private function handleLabelValidationErrors( ValidationError $validationError, array $labelsSerialization ): void {
		$context = $validationError->getContext();
		switch ( $validationError->getCode() ) {
			case LabelsSyntaxValidator::CODE_LABELS_NOT_ASSOCIATIVE:
				$this->throwInvalidField( 'labels', 'labels', $labelsSerialization );
			case LabelsSyntaxValidator::CODE_EMPTY_LABEL:
				throw UseCaseError::newInvalidValue( "/item/labels/{$context[LabelsSyntaxValidator::CONTEXT_LANGUAGE]}" );
			case LabelsSyntaxValidator::CODE_INVALID_LABEL_TYPE:
				$value = json_encode( $context[LabelsSyntaxValidator::CONTEXT_LABEL] );
				throw new UseCaseError(
					UseCaseError::INVALID_LABEL,
					"Not a valid label: {$value}",
					[ UseCaseError::CONTEXT_LANGUAGE => $context[LabelsSyntaxValidator::CONTEXT_LANGUAGE] ]
				);
			case ItemLabelValidator::CODE_INVALID:
				throw new UseCaseError(
					UseCaseError::INVALID_LABEL,
					"Not a valid label: {$context[ItemLabelValidator::CONTEXT_LABEL]}",
					[ UseCaseError::CONTEXT_LANGUAGE => $context[ItemLabelValidator::CONTEXT_LANGUAGE] ]
				);
			case ItemLabelValidator::CODE_TOO_LONG:
				throw new UseCaseError(
					UseCaseError::LABEL_TOO_LONG,
					"Label must be no more than {$context[ItemLabelValidator::CONTEXT_LIMIT]} characters long",
					[
						UseCaseError::CONTEXT_LANGUAGE => $context[ItemLabelValidator::CONTEXT_LANGUAGE],
						UseCaseError::CONTEXT_CHARACTER_LIMIT => $context[ItemLabelValidator::CONTEXT_LIMIT],
					]
				);
			case ItemLabelValidator::CODE_LABEL_SAME_AS_DESCRIPTION:
				throw new UseCaseError(
					UseCaseError::LABEL_DESCRIPTION_SAME_VALUE,
					"Label and description for language '{$context[ItemLabelValidator::CONTEXT_LANGUAGE]}'" .
					' can not have the same value',
					[ UseCaseError::CONTEXT_LANGUAGE => $context[ItemLabelValidator::CONTEXT_LANGUAGE] ]
				);
			case ItemLabelValidator::CODE_LABEL_DESCRIPTION_DUPLICATE:
				throw new UseCaseError(
					UseCaseError::ITEM_LABEL_DESCRIPTION_DUPLICATE,
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
		}
	}

	private function handleDescriptionValidationErrors( ValidationError $validationError, array $descriptionsSerialization ): void {
		$context = $validationError->getContext();
		switch ( $validationError->getCode() ) {
			case DescriptionsSyntaxValidator::CODE_DESCRIPTIONS_NOT_ASSOCIATIVE:
				$this->throwInvalidField( 'descriptions', 'descriptions', $descriptionsSerialization );
			case DescriptionsSyntaxValidator::CODE_EMPTY_DESCRIPTION:
				throw UseCaseError::newInvalidValue(
					"/item/descriptions/{$context[DescriptionsSyntaxValidator::CONTEXT_LANGUAGE]}"
				);
			case DescriptionsSyntaxValidator::CODE_INVALID_DESCRIPTION_TYPE:
				$value = json_encode( $context[DescriptionsSyntaxValidator::CONTEXT_DESCRIPTION] );
				throw new UseCaseError(
					UseCaseError::INVALID_DESCRIPTION,
					"Not a valid description: {$value}",
					[ UseCaseError::CONTEXT_LANGUAGE => $context[DescriptionsSyntaxValidator::CONTEXT_LANGUAGE] ]
				);
			case ItemDescriptionValidator::CODE_INVALID:
				throw new UseCaseError(
					UseCaseError::INVALID_DESCRIPTION,
					"Not a valid description: {$context[ItemDescriptionValidator::CONTEXT_DESCRIPTION]}",
					[ UseCaseError::CONTEXT_LANGUAGE => $context[ItemDescriptionValidator::CONTEXT_LANGUAGE] ]
				);
			case ItemDescriptionValidator::CODE_TOO_LONG:
				throw new UseCaseError(
					UseCaseError::DESCRIPTION_TOO_LONG,
					"Description must be no more than {$context[ItemDescriptionValidator::CONTEXT_LIMIT]} characters long",
					[
						UseCaseError::CONTEXT_LANGUAGE => $context[ItemDescriptionValidator::CONTEXT_LANGUAGE],
						UseCaseError::CONTEXT_CHARACTER_LIMIT => $context[ItemDescriptionValidator::CONTEXT_LIMIT],
					]
				);
			case ItemDescriptionValidator::CODE_DESCRIPTION_SAME_AS_LABEL:
				throw new UseCaseError(
					UseCaseError::LABEL_DESCRIPTION_SAME_VALUE,
					"Label and description for language '{$context[ItemDescriptionValidator::CONTEXT_LANGUAGE]}'" .
					' can not have the same value',
					[ UseCaseError::CONTEXT_LANGUAGE => $context[ItemDescriptionValidator::CONTEXT_LANGUAGE] ]
				);
			case ItemDescriptionValidator::CODE_DESCRIPTION_LABEL_DUPLICATE:
				throw new UseCaseError(
					UseCaseError::ITEM_LABEL_DESCRIPTION_DUPLICATE,
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

	private function handleAliasesValidationErrors( ValidationError $validationError, array $aliasesSerialization ): void {
		$context = $validationError->getContext();
		switch ( $validationError->getCode() ) {
			case AliasesValidator::CODE_INVALID_ALIASES:
				$this->throwInvalidField( 'aliases', 'aliases', $context[AliasesValidator::CONTEXT_ALIASES] );
			case AliasesValidator::CODE_EMPTY_ALIAS:
				throw UseCaseError::newInvalidValue( '/item/aliases' . $context[AliasesValidator::CONTEXT_PATH] );
			case AliasesValidator::CODE_EMPTY_ALIAS_LIST:
				throw UseCaseError::newInvalidValue( '/item/aliases/' . $context[AliasesValidator::CONTEXT_LANGUAGE ] );
			case AliasesValidator::CODE_DUPLICATE_ALIAS:
				throw new UseCaseError(
					UseCaseError::ALIAS_DUPLICATE,
					"Alias list contains a duplicate alias: '{$context[AliasesValidator::CONTEXT_ALIAS]}'",
					[
						UseCaseError::CONTEXT_LANGUAGE => $context[AliasesValidator::CONTEXT_LANGUAGE],
						UseCaseError::CONTEXT_ALIAS => $context[AliasesValidator::CONTEXT_ALIAS],
					]
				);
			case AliasesValidator::CODE_TOO_LONG_ALIAS:
			case AliasesInLanguageValidator::CODE_TOO_LONG:
				$limit = $context[AliasesValidator::CONTEXT_LIMIT] ?? $context[AliasesInLanguageValidator::CONTEXT_LIMIT];
				$language = $context[AliasesValidator::CONTEXT_LANGUAGE] ?? $context[AliasesInLanguageValidator::CONTEXT_LANGUAGE];
				throw new UseCaseError(
					UseCaseError::ALIAS_TOO_LONG,
					"Alias must be no more than $limit characters long",
					[ UseCaseError::CONTEXT_LANGUAGE => $language, UseCaseError::CONTEXT_CHARACTER_LIMIT => $limit ]
				);
			case AliasesValidator::CODE_INVALID_ALIAS_LIST:
				$language = $context[AliasesValidator::CONTEXT_LANGUAGE];
				throw new UseCaseError(
					UseCaseError::INVALID_ALIAS_LIST,
					'Not a valid alias list',
					[ UseCaseError::CONTEXT_LANGUAGE => $language ]
				);
			case AliasesValidator::CODE_INVALID_ALIAS:
			case AliasesInLanguageValidator::CODE_INVALID:
				$aliasValue = $context[AliasesValidator::CONTEXT_ALIAS] ?? $context[AliasesInLanguageValidator::CONTEXT_VALUE];
				$language = $context[AliasesValidator::CONTEXT_LANGUAGE] ?? $context[AliasesInLanguageValidator::CONTEXT_LANGUAGE];
				$aliasIndex = array_search( $aliasValue, $aliasesSerialization[$language] );
				if ( !is_int( $aliasIndex ) ) {
					throw new LogicException( "The invalid alias wasn't found in the original aliases serialization" );
				}
				throw UseCaseError::newInvalidValue( "/item/aliases/$language/$aliasIndex" );
		}
	}

	private function handleStatementsValidationErrors( ValidationError $validationError ): void {
		$context = $validationError->getContext();
		switch ( $validationError->getCode() ) {
			case StatementsValidator::CODE_STATEMENTS_NOT_ASSOCIATIVE:
				$this->throwInvalidField( 'statements', 'statements', $context[StatementsValidator::CONTEXT_STATEMENTS] );
			case StatementsValidator::CODE_STATEMENT_GROUP_NOT_SEQUENTIAL:
			case StatementsValidator::CODE_STATEMENT_NOT_ARRAY:
			case StatementsValidator::CODE_INVALID_STATEMENT_DATA:
				throw UseCaseError::newInvalidValue( '/item/statements/' . $context[StatementsValidator::CONTEXT_PATH] );
			case StatementsValidator::CODE_MISSING_STATEMENT_DATA:
				throw new UseCaseError(
					UseCaseError::STATEMENT_DATA_MISSING_FIELD,
					"Mandatory field missing in the statement data: {$context[StatementsValidator::CONTEXT_FIELD]}",
					[
						UseCaseError::CONTEXT_PATH => $context[StatementsValidator::CONTEXT_PATH],
						UseCaseError::CONTEXT_FIELD => $context[StatementsValidator::CONTEXT_FIELD],
					]
				);
			case StatementsValidator::CODE_PROPERTY_ID_MISMATCH:
				throw new UseCaseError(
					UseCaseError::STATEMENT_GROUP_PROPERTY_ID_MISMATCH,
					"Statement's Property ID does not match the statement group key",
					[
						UseCaseError::CONTEXT_PATH => $context[StatementsValidator::CONTEXT_PATH],
						UseCaseError::CONTEXT_PROPERTY_ID_KEY => $context[StatementsValidator::CONTEXT_PROPERTY_ID_KEY],
						UseCaseError::CONTEXT_PROPERTY_ID_VALUE => $context[StatementsValidator::CONTEXT_PROPERTY_ID_VALUE],
					]
				);
		}
	}

	private function handleSitelinksValidationErrors( ValidationError $validationError, array $serialization ): void {
		$context = $validationError->getContext();
		$siteId = fn() => $context[SitelinkValidator::CONTEXT_SITE_ID];

		switch ( $validationError->getCode() ) {
			case SitelinksValidator::CODE_INVALID_SITELINK:
				throw new UseCaseError(
					UseCaseError::INVALID_SITELINK_TYPE,
					'Not a valid sitelink type',
					[ UseCaseError::CONTEXT_SITE_ID => $context[SitelinksValidator::CONTEXT_SITE_ID] ]
				);
			case SitelinksValidator::CODE_SITELINKS_NOT_ASSOCIATIVE:
				$this->throwInvalidField( 'sitelinks', 'sitelinks', $serialization );
			case SiteIdValidator::CODE_INVALID_SITE_ID:
				throw UseCaseError::newInvalidValue( "/item/sitelinks/{$context[SiteIdValidator::CONTEXT_SITE_ID_VALUE]}" );
			case SitelinkValidator::CODE_TITLE_MISSING:
				throw new UseCaseError(
					UseCaseError::SITELINK_DATA_MISSING_TITLE,
					'Mandatory sitelink title missing',
					[ UseCaseError::CONTEXT_SITE_ID => $siteId() ]
				);
			case SitelinkValidator::CODE_EMPTY_TITLE:
			case SitelinkValidator::CODE_INVALID_TITLE:
			case SitelinkValidator::CODE_INVALID_TITLE_TYPE:
				throw UseCaseError::newInvalidValue( "/item/sitelinks/{$siteId()}/title" );
			case SitelinkValidator::CODE_INVALID_BADGES_TYPE:
				throw new UseCaseError(
					UseCaseError::INVALID_SITELINK_BADGES_FORMAT,
					'Value of badges field is not a list',
					[ UseCaseError::CONTEXT_SITE_ID => $siteId() ]
				);
			case SitelinkValidator::CODE_INVALID_BADGE:
				$badgeIndex = array_search(
					$context[ SitelinkValidator::CONTEXT_BADGE],
					$serialization[ $context[SitelinkValidator::CONTEXT_SITE_ID ] ][ 'badges' ]
				);
				if ( !is_int( $badgeIndex ) ) {
					throw new LogicException( "The invalid operation wasn't found in the original patch document" );
				}
				$path = '/item/sitelinks/' . $context[ SitelinkValidator::CONTEXT_SITE_ID ] . '/badges/' . $badgeIndex;
				throw UseCaseError::newInvalidValue( $path );
			case SitelinkValidator::CODE_BADGE_NOT_ALLOWED:
				$badge = (string)$context[ SitelinkValidator::CONTEXT_BADGE ];
				throw new UseCaseError(
					UseCaseError::ITEM_NOT_A_BADGE,
					"Item ID provided as badge is not allowed as a badge: $badge",
					[
						UseCaseError::CONTEXT_SITE_ID => $siteId(),
						UseCaseError::CONTEXT_BADGE => $badge,
					]
				);
			case SitelinkValidator::CODE_TITLE_NOT_FOUND:
				$title = $serialization[$siteId()]['title'];
				throw new UseCaseError(
					UseCaseError::SITELINK_TITLE_NOT_FOUND,
					"Page with title $title does not exist on the given site",
					[ UseCaseError::CONTEXT_SITE_ID => $siteId() ]
				);
			case SitelinkValidator::CODE_SITELINK_CONFLICT:
				$matchingItemId = $context[ SitelinkValidator::CONTEXT_CONFLICT_ITEM_ID ];
				throw new UseCaseError(
					UseCaseError::SITELINK_CONFLICT,
					"Sitelink is already being used on $matchingItemId",
					[
						UseCaseError::CONTEXT_MATCHING_ITEM_ID => "$matchingItemId",
						UseCaseError::CONTEXT_SITE_ID => $siteId(),
					]
				);
		}
	}

	/**
	 * @param string $field
	 * @param string $path
	 * @param mixed $value
	 *
	 * @return never
	 */
	public function throwInvalidField( string $field, string $path, $value ): void {
		throw new UseCaseError(
			UseCaseError::ITEM_DATA_INVALID_FIELD,
			"Invalid input for '$field'",
			[ UseCaseError::CONTEXT_PATH => $path, UseCaseError::CONTEXT_VALUE => $value ]
		);
	}

}
