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
			throw UseCaseError::newPatchResultModifiedReadOnlyValue( '/id' );
		}
	}

	private function assertValidFields( array $serialization ): void {
		// 'id' is not modifiable and 'type' is ignored, so we only check the expected array fields
		foreach ( [ 'labels', 'descriptions', 'aliases', 'sitelinks', 'statements' ] as $field ) {
			if ( isset( $serialization[$field] ) && !is_array( $serialization[$field] ) ) {
				throw UseCaseError::newPatchResultInvalidValue( "/$field", $serialization[$field] );
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
			$this->handleDescriptionsValidationError( $validationError );
			throw new LogicException( "Unknown validation error: {$validationError->getCode()}" );
		}
	}

	private function handleLanguageCodeValidationError( ValidationError $validationError ): void {
		if ( $validationError->getCode() !== LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE ) {
			return;
		}

		$context = $validationError->getContext();
			throw UseCaseError::newPatchResultInvalidKey(
				'/' . $context[LanguageCodeValidator::CONTEXT_FIELD],
				$context[LanguageCodeValidator::CONTEXT_LANGUAGE_CODE]
			);
	}

	private function handleLabelsValidationError( ValidationError $validationError, array $labelsSerialization ): void {
		$context = $validationError->getContext();

		switch ( $validationError->getCode() ) {
			case LabelsSyntaxValidator::CODE_LABELS_NOT_ASSOCIATIVE:
				throw UseCaseError::newPatchResultInvalidValue( '/labels', $labelsSerialization );
			case LabelsSyntaxValidator::CODE_EMPTY_LABEL:
				$languageCode = $context[LabelsSyntaxValidator::CONTEXT_LANGUAGE];
				throw UseCaseError::newPatchResultInvalidValue( "/labels/$languageCode", '' );
			case LabelsSyntaxValidator::CODE_INVALID_LABEL_TYPE:
				$language = $context[LabelsSyntaxValidator::CONTEXT_LANGUAGE];
				$value = $context[LabelsSyntaxValidator::CONTEXT_LABEL];
				throw UseCaseError::newPatchResultInvalidValue( "/labels/$language", $value );
			case ItemLabelValidator::CODE_INVALID:
				$language = $context[ItemLabelValidator::CONTEXT_LANGUAGE];
				$value = $context[ItemLabelValidator::CONTEXT_LABEL];
				throw UseCaseError::newPatchResultInvalidValue( "/labels/$language", $value );
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
				throw UseCaseError::newDataPolicyViolation(
					UseCaseError::POLICY_VIOLATION_LABEL_DESCRIPTION_SAME_VALUE,
					[ UseCaseError::CONTEXT_LANGUAGE => $context[ItemLabelValidator::CONTEXT_LANGUAGE] ]
				);
		}
	}

	private function handleDescriptionsValidationError( ValidationError $validationError ): void {
		$context = $validationError->getContext();

		switch ( $validationError->getCode() ) {
			case DescriptionsSyntaxValidator::CODE_DESCRIPTIONS_NOT_ASSOCIATIVE:
				throw UseCaseError::newPatchResultInvalidValue( '/descriptions', $context[ DescriptionsSyntaxValidator::CONTEXT_VALUE ] );
			case DescriptionsSyntaxValidator::CODE_EMPTY_DESCRIPTION:
				$languageCode = $context[DescriptionsSyntaxValidator::CONTEXT_LANGUAGE];
				throw UseCaseError::newPatchResultInvalidValue( "/descriptions/$languageCode", '' );
			case DescriptionsSyntaxValidator::CODE_INVALID_DESCRIPTION_TYPE:
				throw UseCaseError::newPatchResultInvalidValue(
					"/descriptions/{$context[DescriptionsSyntaxValidator::CONTEXT_LANGUAGE]}",
					$context[DescriptionsSyntaxValidator::CONTEXT_DESCRIPTION]
				);
			case ItemDescriptionValidator::CODE_INVALID:
				throw UseCaseError::newPatchResultInvalidValue(
					"/descriptions/{$context[ItemDescriptionValidator::CONTEXT_LANGUAGE]}",
					$context[ItemDescriptionValidator::CONTEXT_DESCRIPTION]
				);
			case ItemDescriptionValidator::CODE_TOO_LONG:
				$languageCode = $context[ItemDescriptionValidator::CONTEXT_LANGUAGE];
				$maxDescriptionLength = $context[ItemDescriptionValidator::CONTEXT_LIMIT];
				throw UseCaseError::newValueTooLong( "/descriptions/$languageCode", $maxDescriptionLength, true );
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

	private function assertValidAliases( array $serialization ): void {
		$aliasesSerialization = $serialization[ 'aliases' ] ?? [];
		$validationError = $this->aliasesValidator->validate( $aliasesSerialization, '/aliases' );
		if ( $validationError ) {
			$errorCode = $validationError->getCode();
			$context = $validationError->getContext();
			switch ( $errorCode ) {
				case AliasesValidator::CODE_INVALID_VALUE:
					throw UseCaseError::newPatchResultInvalidValue(
						$context[AliasesValidator::CONTEXT_PATH],
						$context[AliasesValidator::CONTEXT_VALUE]
					);
				case AliasesValidator::CODE_INVALID_ALIASES:
					throw UseCaseError::newPatchResultInvalidValue( '/aliases', $aliasesSerialization );
				case LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE:
					throw UseCaseError::newPatchResultInvalidKey( '/aliases', $context[LanguageCodeValidator::CONTEXT_LANGUAGE_CODE] );
				case AliasesValidator::CODE_INVALID_ALIAS_LIST:
					$language = $context[AliasesValidator::CONTEXT_LANGUAGE];
					throw UseCaseError::newPatchResultInvalidValue( "/aliases/$language", $aliasesSerialization[$language] );
				case AliasesInLanguageValidator::CODE_INVALID:
					throw UseCaseError::newPatchResultInvalidValue(
						$context[AliasesInLanguageValidator::CONTEXT_PATH],
						$context[AliasesInLanguageValidator::CONTEXT_VALUE]
					);
				case AliasesInLanguageValidator::CODE_TOO_LONG:
					$path = $context[AliasesInLanguageValidator::CONTEXT_PATH];
					$limit = $context[AliasesInLanguageValidator::CONTEXT_LIMIT];
					throw UseCaseError::newValueTooLong( $path, $limit, true );
				default:
					throw new LogicException( "Unexpected validation error code: $errorCode" );
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
			$this->getModifiedSitelinksSites( $item->getSitelinks(), $sitelinksSerialization ),
			'/sitelinks'
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
				throw UseCaseError::newPatchResultInvalidValue( '/sitelinks', $sitelinksSerialization );
			case SiteIdValidator::CODE_INVALID_SITE_ID:
				throw UseCaseError::newPatchResultInvalidKey( '/sitelinks', $context[SiteIdValidator::CONTEXT_SITE_ID_VALUE] );
			case SitelinkValidator::CODE_TITLE_MISSING:
				throw UseCaseError::newMissingFieldInPatchResult( $context[SitelinkValidator::CONTEXT_PATH], 'title' );
			case SitelinkValidator::CODE_EMPTY_TITLE:
			case SitelinkValidator::CODE_INVALID_TITLE:
			case SitelinkValidator::CODE_INVALID_FIELD_TYPE:
				throw UseCaseError::newPatchResultInvalidValue(
					$context[SitelinkValidator::CONTEXT_PATH],
					$context[SitelinkValidator::CONTEXT_VALUE]
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
			case SitelinkValidator::CODE_INVALID_BADGE:
			case SitelinkValidator::CODE_BADGE_NOT_ALLOWED:
				$badge = (string)$context[ SitelinkValidator::CONTEXT_VALUE];
				$badgeIndex = Utils::getIndexOfValueInSerialization( $badge, $sitelinksSerialization[$siteId()]['badges'] );
				throw UseCaseError::newPatchResultInvalidValue( "/sitelinks/{$siteId()}/badges/$badgeIndex", $badge );
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
			$serialization['statements'] ?? [],
			'/statements'
		);
		if ( $validationError ) {
			$context = $validationError->getContext();
			switch ( $validationError->getCode() ) {
				case StatementsValidator::CODE_STATEMENTS_NOT_ASSOCIATIVE:
				case StatementsValidator::CODE_STATEMENT_GROUP_NOT_SEQUENTIAL:
				case StatementsValidator::CODE_STATEMENT_NOT_ARRAY:
					throw UseCaseError::newPatchResultInvalidValue(
						$context[StatementsValidator::CONTEXT_PATH],
						$context[StatementsValidator::CONTEXT_VALUE]
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
				case StatementValidator::CODE_INVALID_FIELD_TYPE:
				case StatementValidator::CODE_INVALID_FIELD:
					throw UseCaseError::newPatchResultInvalidValue(
						$context[StatementValidator::CONTEXT_PATH],
						$context[StatementValidator::CONTEXT_VALUE]
					);
				case StatementValidator::CODE_MISSING_FIELD:
					throw UseCaseError::newMissingFieldInPatchResult(
						$context[StatementValidator::CONTEXT_PATH],
						$context[StatementValidator::CONTEXT_FIELD]
					);
			}
		}

		// get StatementIds for all Statements in a StatementList, removing any that are null
		$getStatementIds = fn( StatementList $statementList ) => array_filter( array_map(
			fn( Statement $statement ) => $statement->getGuid(),
			iterator_to_array( $statementList )
		) );

		$getStatementIdPath = function( array $serialization, string $id ): string {
			foreach ( $serialization as $propertyId => $statementGroup ) {
				foreach ( $statementGroup as $groupIndex => $statement ) {
					if ( isset( $statement['id'] ) && $statement['id'] === $id ) {
						return "/statements/$propertyId/$groupIndex";
					}
				}
			}

			throw new LogicException( "Statement ID '$id' not found in patch result" );
		};

		$originalStatements = $originalItem->getStatements();
		$originalStatementsIds = $getStatementIds( $originalStatements );
		$patchedStatements = $this->statementsValidator->getValidatedStatements();
		$patchedStatementsIds = $getStatementIds( $patchedStatements );
		foreach ( array_count_values( $patchedStatementsIds ) as $id => $occurrence ) {
			if ( $occurrence > 1 || !in_array( $id, $originalStatementsIds ) ) {
				$path = "{$getStatementIdPath( $serialization['statements'], $id )}/id";
				throw UseCaseError::newPatchResultModifiedReadOnlyValue( $path );
			}

			$originalPropertyId = $originalStatements->getFirstStatementWithGuid( $id )->getPropertyId();
			if ( !$patchedStatements->getFirstStatementWithGuid( $id )->getPropertyId()->equals(
				$originalPropertyId
			) ) {
				$path = "{$getStatementIdPath( $serialization['statements'], $id )}/property/id";
				throw UseCaseError::newPatchResultModifiedReadOnlyValue( $path );
			}
		}
	}

}
