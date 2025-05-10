<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation;

use LogicException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Application\Validation\AliasesInLanguageValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\AliasesValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\DescriptionsSyntaxValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\ItemDescriptionValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\ItemLabelValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\ItemValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\LabelsSyntaxValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\SiteIdValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\SitelinksValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\SitelinkValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\StatementsValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\StatementValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\ValidationError;

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
		$validationError = $this->validator->validate( $itemSerialization, '/item' );

		if ( $validationError ) {
			$context = $validationError->getContext();
			switch ( $validationError->getCode() ) {
				case ItemValidator::CODE_INVALID_FIELD:
					$field = $context[ItemValidator::CONTEXT_FIELD];
					throw UseCaseError::newInvalidValue( "/item/$field" );
				case LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE:
					throw UseCaseError::newInvalidKey(
						$context[LanguageCodeValidator::CONTEXT_PATH],
						$context[LanguageCodeValidator::CONTEXT_LANGUAGE_CODE]
					);
			}

			$this->handleLabelValidationErrors( $validationError );
			$this->handleDescriptionValidationErrors( $validationError );
			$this->handleAliasesValidationErrors( $validationError );
			$this->handleStatementsValidationErrors( $validationError );
			$this->handleSitelinksValidationErrors( $validationError, $itemSerialization['sitelinks'] ?? [] );

			throw new LogicException( "Unknown validation error code: {$validationError->getCode()}" );
		}

		return $this->validator->getValidatedItem();
	}

	private function handleLabelValidationErrors( ValidationError $validationError ): void {
		$context = $validationError->getContext();
		switch ( $validationError->getCode() ) {
			case LabelsSyntaxValidator::CODE_LABELS_NOT_ASSOCIATIVE:
				throw UseCaseError::newInvalidValue( '/item/labels' );
			case LabelsSyntaxValidator::CODE_EMPTY_LABEL:
			case LabelsSyntaxValidator::CODE_INVALID_LABEL_TYPE:
				throw UseCaseError::newInvalidValue( "/item/labels/{$context[LabelsSyntaxValidator::CONTEXT_LANGUAGE]}" );
			case ItemLabelValidator::CODE_INVALID:
				throw UseCaseError::newInvalidValue( "/item/labels/{$context[ItemLabelValidator::CONTEXT_LANGUAGE]}" );
			case ItemLabelValidator::CODE_TOO_LONG:
				throw UseCaseError::newValueTooLong(
					"/item/labels/{$context[ItemLabelValidator::CONTEXT_LANGUAGE]}",
					$context[ItemLabelValidator::CONTEXT_LIMIT]
				);
			case ItemLabelValidator::CODE_LABEL_SAME_AS_DESCRIPTION:
				throw UseCaseError::newDataPolicyViolation(
					UseCaseError::POLICY_VIOLATION_LABEL_DESCRIPTION_SAME_VALUE,
					[ UseCaseError::CONTEXT_LANGUAGE => $context[ItemLabelValidator::CONTEXT_LANGUAGE] ]
				);
			case ItemLabelValidator::CODE_LABEL_DESCRIPTION_DUPLICATE:
				throw UseCaseError::newDataPolicyViolation(
					UseCaseError::POLICY_VIOLATION_ITEM_LABEL_DESCRIPTION_DUPLICATE,
					[
						UseCaseError::CONTEXT_LANGUAGE => $context[ItemLabelValidator::CONTEXT_LANGUAGE],
						UseCaseError::CONTEXT_CONFLICTING_ITEM_ID => $context[ItemLabelValidator::CONTEXT_CONFLICTING_ITEM_ID],
					]
				);
		}
	}

	private function handleDescriptionValidationErrors( ValidationError $validationError ): void {
		$context = $validationError->getContext();
		switch ( $validationError->getCode() ) {
			case DescriptionsSyntaxValidator::CODE_DESCRIPTIONS_NOT_ASSOCIATIVE:
				throw UseCaseError::newInvalidValue( '/item/descriptions' );
			case DescriptionsSyntaxValidator::CODE_EMPTY_DESCRIPTION:
				throw UseCaseError::newInvalidValue(
					"/item/descriptions/{$context[DescriptionsSyntaxValidator::CONTEXT_LANGUAGE]}"
				);
			case DescriptionsSyntaxValidator::CODE_INVALID_DESCRIPTION_TYPE:
				throw UseCaseError::newInvalidValue(
					"/item/descriptions/{$context[DescriptionsSyntaxValidator::CONTEXT_LANGUAGE]}"
				);
			case ItemDescriptionValidator::CODE_INVALID:
				throw UseCaseError::newInvalidValue( "/item/descriptions/{$context[ItemDescriptionValidator::CONTEXT_LANGUAGE]}" );
			case ItemDescriptionValidator::CODE_TOO_LONG:
				throw UseCaseError::newValueTooLong(
					"/item/descriptions/{$context[ItemDescriptionValidator::CONTEXT_LANGUAGE]}",
					$context[ItemDescriptionValidator::CONTEXT_LIMIT],
				);
			case ItemDescriptionValidator::CODE_DESCRIPTION_SAME_AS_LABEL:
				throw UseCaseError::newDataPolicyViolation(
					UseCaseError::POLICY_VIOLATION_LABEL_DESCRIPTION_SAME_VALUE,
					[ UseCaseError::CONTEXT_LANGUAGE => $context[ItemDescriptionValidator::CONTEXT_LANGUAGE] ]
				);
			case ItemDescriptionValidator::CODE_DESCRIPTION_LABEL_DUPLICATE:
				throw UseCaseError::newDataPolicyViolation(
					UseCaseError::POLICY_VIOLATION_ITEM_LABEL_DESCRIPTION_DUPLICATE,
					[
						UseCaseError::CONTEXT_LANGUAGE => $context[ItemDescriptionValidator::CONTEXT_LANGUAGE],
						UseCaseError::CONTEXT_CONFLICTING_ITEM_ID => $context[ItemDescriptionValidator::CONTEXT_CONFLICTING_ITEM_ID],
					]
				);
		}
	}

	private function handleAliasesValidationErrors( ValidationError $validationError ): void {
		$context = $validationError->getContext();
		switch ( $validationError->getCode() ) {
			case AliasesValidator::CODE_INVALID_VALUE:
				throw UseCaseError::newInvalidValue( $context[AliasesValidator::CONTEXT_PATH] );
			case AliasesInLanguageValidator::CODE_TOO_LONG:
				throw UseCaseError::newValueTooLong(
					$context[AliasesInLanguageValidator::CONTEXT_PATH],
					$context[AliasesInLanguageValidator::CONTEXT_LIMIT]
				);
			case AliasesInLanguageValidator::CODE_INVALID:
				throw UseCaseError::newInvalidValue( $context[AliasesInLanguageValidator::CONTEXT_PATH] );
		}
	}

	private function handleStatementsValidationErrors( ValidationError $validationError ): void {
		$context = $validationError->getContext();
		switch ( $validationError->getCode() ) {
			case StatementsValidator::CODE_STATEMENTS_NOT_ASSOCIATIVE:
			case StatementsValidator::CODE_STATEMENT_GROUP_NOT_SEQUENTIAL:
			case StatementsValidator::CODE_STATEMENT_NOT_ARRAY:
				throw UseCaseError::newInvalidValue( $context[StatementsValidator::CONTEXT_PATH] );
			case StatementValidator::CODE_INVALID_FIELD:
				throw UseCaseError::newInvalidValue( $context[StatementValidator::CONTEXT_PATH] );
			case StatementValidator::CODE_PROPERTY_NOT_FOUND:
				throw UseCaseError::newReferencedResourceNotFound( $context[StatementValidator::CONTEXT_PATH] );
			case StatementValidator::CODE_INVALID_FIELD_TYPE:
				throw UseCaseError::newInvalidValue( $context[StatementValidator::CONTEXT_PATH] );
			case StatementValidator::CODE_MISSING_FIELD:
				throw UseCaseError::newMissingField(
					$context[StatementValidator::CONTEXT_PATH],
					$context[StatementValidator::CONTEXT_FIELD]
				);
			case StatementsValidator::CODE_PROPERTY_ID_MISMATCH:
				throw new UseCaseError(
					UseCaseError::STATEMENT_GROUP_PROPERTY_ID_MISMATCH,
					"Statement's Property ID does not match the Statement group key",
					[
						UseCaseError::CONTEXT_PATH => $context[StatementsValidator::CONTEXT_PATH],
						UseCaseError::CONTEXT_STATEMENT_GROUP_PROPERTY_ID => $context[StatementsValidator::CONTEXT_PROPERTY_ID_KEY],
						UseCaseError::CONTEXT_STATEMENT_PROPERTY_ID => $context[StatementsValidator::CONTEXT_PROPERTY_ID_VALUE],
					]
				);
		}
	}

	private function handleSitelinksValidationErrors( ValidationError $validationError, array $serialization ): void {
		$context = $validationError->getContext();
		$siteId = fn() => $context[SitelinkValidator::CONTEXT_SITE_ID];

		switch ( $validationError->getCode() ) {
			case SitelinksValidator::CODE_INVALID_SITELINK:
				throw UseCaseError::newInvalidValue( "/item/sitelinks/{$context[SitelinksValidator::CONTEXT_SITE_ID]}" );
			case SitelinksValidator::CODE_SITELINKS_NOT_ASSOCIATIVE:
				throw UseCaseError::newInvalidValue( '/item/sitelinks' );
			case SiteIdValidator::CODE_INVALID_SITE_ID:
				$invalidSiteId = $context[SiteIdValidator::CONTEXT_SITE_ID_VALUE];
				throw UseCaseError::newInvalidKey( '/item/sitelinks', $invalidSiteId );
			case SitelinkValidator::CODE_TITLE_MISSING:
				throw UseCaseError::newMissingField( $context[SitelinkValidator::CONTEXT_PATH], 'title' );
			case SitelinkValidator::CODE_EMPTY_TITLE:
			case SitelinkValidator::CODE_INVALID_TITLE:
			case SitelinkValidator::CODE_INVALID_FIELD_TYPE:
				throw UseCaseError::newInvalidValue( $context[SitelinkValidator::CONTEXT_PATH] );
			case SitelinkValidator::CODE_INVALID_BADGE:
			case SitelinkValidator::CODE_BADGE_NOT_ALLOWED:
				$badge = $context[SitelinkValidator::CONTEXT_VALUE];
				$badgeIndex = Utils::getIndexOfValueInSerialization( $badge, $serialization[$siteId()][ 'badges' ] );
				throw UseCaseError::newInvalidValue( "/item/sitelinks/{$siteId()}/badges/$badgeIndex" );
			case SitelinkValidator::CODE_TITLE_NOT_FOUND:
				throw UseCaseError::newReferencedResourceNotFound( "/item/sitelinks/{$siteId()}/title" );
			case SitelinkValidator::CODE_SITELINK_CONFLICT:
				$conflictingItemId = $context[ SitelinkValidator::CONTEXT_CONFLICTING_ITEM_ID ];
				throw UseCaseError::newDataPolicyViolation(
					UseCaseError::POLICY_VIOLATION_SITELINK_CONFLICT,
					[
						UseCaseError::CONTEXT_CONFLICTING_ITEM_ID => "$conflictingItemId",
						UseCaseError::CONTEXT_SITE_ID => $siteId(),
					]
				);
		}
	}

}
