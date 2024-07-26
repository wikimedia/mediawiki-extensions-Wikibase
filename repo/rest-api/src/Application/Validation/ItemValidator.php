<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use LogicException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Term\Fingerprint;

/**
 * @license GPL-2.0-or-later
 */
class ItemValidator {

	public const CODE_INVALID_FIELD = 'item-validator-code-invalid-item-field';
	public const CODE_UNEXPECTED_FIELD = 'item-validator-code-item-data-unexpected-field';

	public const CONTEXT_FIELD = 'item-validator-context-field';
	public const CONTEXT_VALUE = 'item-validator-context-value';

	private ?Item $deserializedItem = null;
	private LabelsSyntaxValidator $labelsSyntaxValidator;
	private ItemLabelsContentsValidator $labelsContentsValidator;
	private DescriptionsSyntaxValidator $descriptionsSyntaxValidator;
	private ItemDescriptionsContentsValidator $descriptionsContentsValidator;
	private AliasesValidator $itemAliasesValidator;
	private StatementsValidator $itemStatementsValidator;
	private SitelinksValidator $sitelinksValidator;

	public function __construct(
		LabelsSyntaxValidator $labelsSyntaxValidator,
		ItemLabelsContentsValidator $labelsContentsValidator,
		DescriptionsSyntaxValidator $descriptionsSyntaxValidator,
		ItemDescriptionsContentsValidator $descriptionsContentsValidator,
		AliasesValidator $itemAliasesValidator,
		StatementsValidator $itemStatementsValidator,
		SitelinksValidator $sitelinksValidator
	) {
		$this->labelsSyntaxValidator = $labelsSyntaxValidator;
		$this->labelsContentsValidator = $labelsContentsValidator;
		$this->descriptionsSyntaxValidator = $descriptionsSyntaxValidator;
		$this->descriptionsContentsValidator = $descriptionsContentsValidator;
		$this->itemAliasesValidator = $itemAliasesValidator;
		$this->itemStatementsValidator = $itemStatementsValidator;
		$this->sitelinksValidator = $sitelinksValidator;
	}

	public function validate( array $serialization ): ?ValidationError {
		$expectedFields = [ 'labels', 'descriptions', 'aliases', 'sitelinks', 'statements' ];
		foreach ( $expectedFields as $expectedField ) {
			$serialization[$expectedField] ??= [];
			if ( !is_array( $serialization[$expectedField] ) ) {
				return new ValidationError(
					self::CODE_INVALID_FIELD,
					[
						self::CONTEXT_FIELD => $expectedField,
						self::CONTEXT_VALUE => $serialization[$expectedField],
					]
				);
			}
		}

		foreach ( array_keys( $serialization ) as $field ) {
			$ignoredFields = [ 'id', 'type' ];
			if ( !in_array( $field, array_merge( $expectedFields, $ignoredFields ) ) ) {
				return new ValidationError( self::CODE_UNEXPECTED_FIELD, [ self::CONTEXT_FIELD => $field ] );
			}
		}

		$validationError = $this->validateLabelsAndDescriptions( $serialization ) ??
			$this->itemAliasesValidator->validate( $serialization['aliases'] ) ??
			$this->itemStatementsValidator->validate( $serialization['statements'] ) ??
			$this->sitelinksValidator->validate( null, $serialization['sitelinks'] );
		if ( $validationError ) {
			return $validationError;
		}

		$this->deserializedItem = new Item(
			null,
			new Fingerprint(
				$this->labelsContentsValidator->getValidatedLabels(),
				$this->descriptionsContentsValidator->getValidatedDescriptions(),
				$this->itemAliasesValidator->getValidatedAliases()
			),
			$this->sitelinksValidator->getValidatedSitelinks(),
			$this->itemStatementsValidator->getValidatedStatements()
		);

		return null;
	}

	public function getValidatedItem(): Item {
		if ( $this->deserializedItem === null ) {
			throw new LogicException( 'getValidatedItem() called before validate()' );
		}
		return $this->deserializedItem;
	}

	private function validateLabelsAndDescriptions( array $itemSerialization ): ?ValidationError {
		return $this->labelsSyntaxValidator->validate( $itemSerialization['labels'] ) ??
			$this->descriptionsSyntaxValidator->validate( $itemSerialization['descriptions'] ) ??
			$this->labelsContentsValidator->validate(
				$this->labelsSyntaxValidator->getPartiallyValidatedLabels(),
				$this->descriptionsSyntaxValidator->getPartiallyValidatedDescriptions()
			) ??
			$this->descriptionsContentsValidator->validate(
				$this->descriptionsSyntaxValidator->getPartiallyValidatedDescriptions(),
				$this->labelsSyntaxValidator->getPartiallyValidatedLabels()
			);
	}

}
