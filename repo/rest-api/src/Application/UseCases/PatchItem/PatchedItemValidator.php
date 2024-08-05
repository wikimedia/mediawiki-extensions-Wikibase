<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItem;

use LogicException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\Utils;
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
use Wikibase\Repo\RestApi\Application\Validation\SiteIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\SitelinksValidator;
use Wikibase\Repo\RestApi\Application\Validation\SitelinkValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementsValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\RestApi\Domain\ReadModel\Item as ItemReadModel;
use Wikibase\Repo\RestApi\Domain\ReadModel\Sitelinks;

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
	private SitelinksValidator $sitelinksValidator;
	private StatementsValidator $statementsValidator;

	public function __construct(
		LabelsSyntaxValidator $labelsSyntaxValidator,
		ItemLabelsContentsValidator $labelsContentsValidator,
		DescriptionsSyntaxValidator $descriptionsSyntaxValidator,
		ItemDescriptionsContentsValidator $descriptionsContentsValidator,
		AliasesValidator $aliasesValidator,
		SitelinksValidator $sitelinksValidator,
		StatementsValidator $statementsValidator
	) {
		$this->labelsSyntaxValidator = $labelsSyntaxValidator;
		$this->labelsContentsValidator = $labelsContentsValidator;
		$this->descriptionsSyntaxValidator = $descriptionsSyntaxValidator;
		$this->descriptionsContentsValidator = $descriptionsContentsValidator;
		$this->aliasesValidator = $aliasesValidator;
		$this->sitelinksValidator = $sitelinksValidator;
		$this->statementsValidator = $statementsValidator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize(
		ItemReadModel $item,
		array $serialization,
		Item $originalItem,
		array $originalSerialization
	): Item {
		if ( !isset( $serialization[ 'id' ] ) ) { // ignore ID removal
			$serialization[ 'id' ] = $originalItem->getId()->getSerialization();
		}

		$this->assertNoIllegalModification( $serialization, $originalItem );
		$this->assertNoUnexpectedFields( $serialization );
		$this->assertValidFields( $serialization );
		$this->assertValidLabelsAndDescriptions( $serialization, $originalItem );
		$this->assertValidAliases( $serialization );
		$this->assertValidSitelinks( $item, $serialization );
		$this->assertValidStatements( $serialization, $originalItem, $originalSerialization['statements'] );

		return new Item(
			new ItemId( $serialization[ 'id' ] ),
			new Fingerprint(
				$this->labelsContentsValidator->getValidatedLabels(),
				$this->descriptionsContentsValidator->getValidatedDescriptions(),
				$this->aliasesValidator->getValidatedAliases()
			),
			$this->sitelinksValidator->getValidatedSitelinks(),
			$this->statementsValidator->getValidatedStatements()
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
		switch ( $context[LanguageCodeValidator::CONTEXT_FIELD] ) {
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
				throw UseCaseError::newValueTooLong( "/labels/$language", $maxLabelLength, true );
			case ItemLabelValidator::CODE_LABEL_DESCRIPTION_DUPLICATE:
				throw UseCaseError::newDataPolicyViolation(
					UseCaseError::POLICY_VIOLATION_ITEM_LABEL_DESCRIPTION_DUPLICATE,
					[
						UseCaseError::CONTEXT_LANGUAGE => $context[ItemLabelValidator::CONTEXT_LANGUAGE],
						UseCaseError::CONTEXT_CONFLICTING_ITEM_ID => $context[ItemLabelValidator::CONTEXT_CONFLICTING_ITEM_ID],
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
				throw UseCaseError::newValueTooLong( "/descriptions/$languageCode", $maxDescriptionLength, true );
			case ItemDescriptionValidator::CODE_DESCRIPTION_SAME_AS_LABEL:
				$language = $context[ItemDescriptionValidator::CONTEXT_LANGUAGE];
				throw new UseCaseError(
					UseCaseError::PATCHED_ITEM_LABEL_DESCRIPTION_SAME_VALUE,
					"Label and description for language code '{$language}' can not have the same value",
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
					$aliasValue = $context[AliasesInLanguageValidator::CONTEXT_VALUE];
					$aliasIndex = Utils::getIndexOfValueInSerialization( $aliasValue, $aliasesSerialization[$language] );
					throw UseCaseError::newValueTooLong( "/aliases/$language/$aliasIndex", $limit, true );
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

	private function assertValidSitelinks( ItemReadModel $item, array $serialization ): void {
		$itemId = $serialization['id'];
		$sitelinksSerialization = $serialization['sitelinks'] ?? [];
		$originalSitelinks = $item->getSitelinks();
		$validationError = $this->sitelinksValidator->validate(
			$itemId,
			$sitelinksSerialization,
			$this->getModifiedSitelinksSites( $item->getSitelinks(), $sitelinksSerialization )
		);

		if ( $validationError ) {
			$this->handleSitelinksValidationError( $validationError, $sitelinksSerialization );
		}
		$this->assertUrlsNotModified( $originalSitelinks, $sitelinksSerialization );
	}

	private function getModifiedSitelinksSites( Sitelinks $originalSitelinks, array $patchedSitelinks ): array {
		return array_filter(
			array_keys( $patchedSitelinks ),
			function( string $siteId ) use ( $patchedSitelinks, $originalSitelinks ) {
				$originalBadges = fn() => array_map( fn( ItemId $i ) => (string)$i, $originalSitelinks[$siteId]->getBadges() );

				return !isset( $originalSitelinks[$siteId] )
					|| ( $patchedSitelinks[$siteId]['title'] ?? '' ) !== $originalSitelinks[$siteId]->getTitle()
					|| ( $patchedSitelinks[$siteId]['badges'] ?? [] ) !== $originalBadges();
			}
		);
	}

	private function handleSitelinksValidationError( ValidationError $validationError, array $sitelinksSerialization ): void {
		$context = $validationError->getContext();
		$siteId = fn() => $context[ SitelinkValidator::CONTEXT_SITE_ID ];
		switch ( $validationError->getCode() ) {
			case SitelinksValidator::CODE_INVALID_SITELINK:
				throw new UseCaseError(
					UseCaseError::PATCHED_INVALID_SITELINK_TYPE,
					'Not a valid sitelink type in patched sitelinks',
					[ UseCaseError::CONTEXT_SITE_ID => $context[ SitelinksValidator::CONTEXT_SITE_ID ] ]
				);
			case SitelinksValidator::CODE_SITELINKS_NOT_ASSOCIATIVE:
				$this->throwInvalidField( 'sitelinks', $sitelinksSerialization );
			case SiteIdValidator::CODE_INVALID_SITE_ID:
				throw new UseCaseError(
					UseCaseError::PATCHED_SITELINK_INVALID_SITE_ID,
					"Not a valid site ID '{$context[SiteIdValidator::CONTEXT_SITE_ID_VALUE]}' in patched sitelinks",
					[ UseCaseError::CONTEXT_SITE_ID => $context[ SiteIdValidator::CONTEXT_SITE_ID_VALUE ] ]
				);
			case SitelinkValidator::CODE_TITLE_MISSING:
				throw new UseCaseError(
					UseCaseError::PATCHED_SITELINK_MISSING_TITLE,
					"No sitelink title provided for site '{$siteId()}' in patched sitelinks",
					[ UseCaseError::CONTEXT_SITE_ID => $siteId() ]
				);
			case SitelinkValidator::CODE_EMPTY_TITLE:
				throw new UseCaseError(
					UseCaseError::PATCHED_SITELINK_TITLE_EMPTY,
					"Sitelink cannot be empty for site '{$siteId()}' in patched sitelinks",
					[ UseCaseError::CONTEXT_SITE_ID => $siteId() ]
				);
			case SitelinkValidator::CODE_INVALID_TITLE:
			case SitelinkValidator::CODE_INVALID_TITLE_TYPE:
				$title = $sitelinksSerialization[ $siteId() ][ 'title' ];
				throw new UseCaseError(
					UseCaseError::PATCHED_SITELINK_INVALID_TITLE,
					"Invalid sitelink title '$title' for site '{$siteId()}' in patched sitelinks",
					[
						UseCaseError::CONTEXT_SITE_ID => $siteId(),
						UseCaseError::CONTEXT_TITLE => $title,
					]
				);
			case SitelinkValidator::CODE_TITLE_NOT_FOUND:
				$title = $sitelinksSerialization[ $siteId() ][ 'title' ];
				throw new UseCaseError(
					UseCaseError::PATCHED_SITELINK_TITLE_DOES_NOT_EXIST,
					"Incorrect patched sitelinks. Page with title '$title' does not exist on site '{$siteId()}'",
					[
						UseCaseError::CONTEXT_SITE_ID => $siteId(),
						UseCaseError::CONTEXT_TITLE => $title,
					]
				);
			case SitelinkValidator::CODE_INVALID_BADGES_TYPE:
				throw new UseCaseError(
					UseCaseError::PATCHED_SITELINK_BADGES_FORMAT,
					"Badges value for site '{$siteId()}' is not a list in patched sitelinks",
					[
						UseCaseError::CONTEXT_SITE_ID => $siteId(),
						UseCaseError::CONTEXT_BADGES => $sitelinksSerialization[ $siteId() ][ 'badges' ],
					]
				);
			case SitelinkValidator::CODE_INVALID_BADGE:
				$badge = $context[ SitelinkValidator::CONTEXT_BADGE ];
				throw new UseCaseError(
					UseCaseError::PATCHED_SITELINK_INVALID_BADGE,
					"Incorrect patched sitelinks. Badge value '$badge' for site '{$siteId()}' is not an item ID",
					[
						UseCaseError::CONTEXT_SITE_ID => $siteId(),
						UseCaseError::CONTEXT_BADGE => $badge,
					]
				);
			case SitelinkValidator::CODE_BADGE_NOT_ALLOWED:
				$badge = (string)$context[ SitelinkValidator::CONTEXT_BADGE ];
				throw new UseCaseError(
					UseCaseError::PATCHED_SITELINK_ITEM_NOT_A_BADGE,
					"Incorrect patched sitelinks. Item '$badge' used for site '{$siteId()}' is not allowed as a badge",
					[
						UseCaseError::CONTEXT_SITE_ID => $siteId(),
						UseCaseError::CONTEXT_BADGE => $badge,
					]
				);
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

	private function assertUrlsNotModified( Sitelinks $originalSitelinks, array $patchedSitelinkSerialization ): void {
		foreach ( $patchedSitelinkSerialization as $siteId => $sitelink ) {
			if (
				isset( $sitelink[ 'url' ] ) &&
				isset( $originalSitelinks[ $siteId ] ) &&
				$originalSitelinks[ $siteId ]->getUrl() !== $sitelink[ 'url' ]
			) {
				throw new UseCaseError(
					UseCaseError::PATCHED_SITELINK_URL_NOT_MODIFIABLE,
					'URL of sitelink cannot be modified',
					[ UseCaseError::CONTEXT_SITE_ID => $siteId ]
				);
			}
		}
	}

	private function assertValidStatements( array $serialization, Item $originalItem, array $originalStatementsSerialization ): void {
		$validationError = $this->statementsValidator->validateModifiedStatements(
			$originalStatementsSerialization,
			$originalItem->getStatements(),
			$serialization['statements'] ?? []
		);
		if ( $validationError ) {
			$context = $validationError->getContext();
			switch ( $validationError->getCode() ) {
				case StatementsValidator::CODE_STATEMENTS_NOT_ASSOCIATIVE:
					$this->throwInvalidField( 'statements', $context[StatementsValidator::CONTEXT_STATEMENTS] );
				case StatementsValidator::CODE_STATEMENT_GROUP_NOT_SEQUENTIAL:
					throw new UseCaseError(
						UseCaseError::PATCHED_INVALID_STATEMENT_GROUP_TYPE,
						'Not a valid statement group',
						// TODO: the path will be converted into a proper JSON Pointer in a future task
						[ UseCaseError::CONTEXT_PATH => substr( $context[StatementsValidator::CONTEXT_PATH], 1 ) ]
					);
				case StatementsValidator::CODE_PROPERTY_ID_MISMATCH:
					throw new UseCaseError(
						UseCaseError::PATCHED_STATEMENT_GROUP_PROPERTY_ID_MISMATCH,
						"Statement's Property ID does not match the statement group key",
						[
							UseCaseError::CONTEXT_PATH => $context[StatementsValidator::CONTEXT_PATH],
							UseCaseError::CONTEXT_STATEMENT_GROUP_PROPERTY_ID => $context[StatementsValidator::CONTEXT_PROPERTY_ID_KEY],
							UseCaseError::CONTEXT_STATEMENT_PROPERTY_ID => $context[StatementsValidator::CONTEXT_PROPERTY_ID_VALUE],
						]
					);
				case StatementsValidator::CODE_STATEMENT_NOT_ARRAY:
					throw new UseCaseError(
						UseCaseError::PATCHED_INVALID_STATEMENT_TYPE,
						'Not a valid statement type',
						// TODO: the path will be converted into a proper JSON Pointer in a future task
						[ UseCaseError::CONTEXT_PATH => substr( $context[StatementsValidator::CONTEXT_PATH], 1 ) ]
					);
				case StatementValidator::CODE_INVALID_FIELD_TYPE:
					throw new UseCaseError(
						UseCaseError::PATCHED_INVALID_STATEMENT_TYPE,
						'Not a valid statement type',
						// TODO: the path will be converted into a proper JSON Pointer in a future task
						[ UseCaseError::CONTEXT_PATH => substr( $context[StatementValidator::CONTEXT_FIELD], 1 ) ]
					);
				case StatementValidator::CODE_MISSING_FIELD:
					$field = $context[StatementValidator::CONTEXT_FIELD];
					throw new UseCaseError(
						UseCaseError::PATCHED_STATEMENT_MISSING_FIELD,
						"Mandatory field missing in the patched statement: {$field}",
						[ UseCaseError::CONTEXT_PATH => $field ]
					);
				case StatementValidator::CODE_INVALID_FIELD:
					$field = $context[StatementValidator::CONTEXT_FIELD];
					throw new UseCaseError(
						UseCaseError::PATCHED_STATEMENT_INVALID_FIELD,
						"Invalid input for '{$field}' in the patched statement",
						[
							UseCaseError::CONTEXT_PATH => $field,
							UseCaseError::CONTEXT_VALUE => $context[StatementValidator::CONTEXT_VALUE],
						]
					);
			}
		}

		// get StatementIds for all Statements in a StatementList, removing any that are null
		$getStatementIds = fn( StatementList $statementList ) => array_filter( array_map(
			fn( Statement $statement ) => $statement->getGuid(),
			iterator_to_array( $statementList )
		) );

		$originalStatements = $originalItem->getStatements();
		$originalStatementsIds = $getStatementIds( $originalStatements );
		$patchedStatements = $this->statementsValidator->getValidatedStatements();
		$patchedStatementsIds = $getStatementIds( $patchedStatements );
		foreach ( array_count_values( $patchedStatementsIds ) as $id => $occurrence ) {
			if ( $occurrence > 1 || !in_array( $id, $originalStatementsIds ) ) {
				throw new UseCaseError(
					UseCaseError::STATEMENT_ID_NOT_MODIFIABLE,
					'Statement IDs cannot be created or modified',
					[ UseCaseError::CONTEXT_STATEMENT_ID => $id ]
				);
			}

			$originalPropertyId = $originalStatements->getFirstStatementWithGuid( $id )->getPropertyId();
			if ( !$patchedStatements->getFirstStatementWithGuid( $id )->getPropertyId()->equals(
				$originalPropertyId
			) ) {
				throw new UseCaseError(
					UseCaseError::PATCHED_STATEMENT_PROPERTY_NOT_MODIFIABLE,
					'Property of a statement cannot be modified',
					[
						UseCaseError::CONTEXT_STATEMENT_ID => $id,
						UseCaseError::CONTEXT_STATEMENT_PROPERTY_ID => $originalPropertyId->getSerialization(),
					]
				);
			}
		}
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
