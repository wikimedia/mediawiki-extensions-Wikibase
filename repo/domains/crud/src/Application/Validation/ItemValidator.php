<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\Validation;

use LogicException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Term\Fingerprint;

/**
 * @license GPL-2.0-or-later
 */
class ItemValidator {

	public const CODE_INVALID_FIELD = 'item-validator-code-invalid-item-field';

	public const CONTEXT_FIELD = 'item-validator-context-field';
	public const CONTEXT_VALUE = 'item-validator-context-value';

	private ?Item $deserializedItem = null;
	private LabelsSyntaxValidator $labelsSyntaxValidator;
	private ItemLabelsContentsValidator $labelsContentsValidator;
	private DescriptionsSyntaxValidator $descriptionsSyntaxValidator;
	private ItemDescriptionsContentsValidator $descriptionsContentsValidator;
	private AliasesValidator $aliasesValidator;
	private StatementsValidator $itemStatementsValidator;
	private SitelinksValidator $sitelinksValidator;

	public function __construct(
		LabelsSyntaxValidator $labelsSyntaxValidator,
		ItemLabelsContentsValidator $labelsContentsValidator,
		DescriptionsSyntaxValidator $descriptionsSyntaxValidator,
		ItemDescriptionsContentsValidator $descriptionsContentsValidator,
		AliasesValidator $aliasesValidator,
		StatementsValidator $itemStatementsValidator,
		SitelinksValidator $sitelinksValidator
	) {
		$this->labelsSyntaxValidator = $labelsSyntaxValidator;
		$this->labelsContentsValidator = $labelsContentsValidator;
		$this->descriptionsSyntaxValidator = $descriptionsSyntaxValidator;
		$this->descriptionsContentsValidator = $descriptionsContentsValidator;
		$this->aliasesValidator = $aliasesValidator;
		$this->itemStatementsValidator = $itemStatementsValidator;
		$this->sitelinksValidator = $sitelinksValidator;
	}

	public function validate( array $serialization, string $basePath = '' ): ?ValidationError {
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

		$validationError = $this->validateLabelsAndDescriptions( $serialization, $basePath ) ??
			$this->aliasesValidator->validate( $serialization['aliases'], "$basePath/aliases" ) ??
			$this->itemStatementsValidator->validate( $serialization['statements'], "$basePath/statements" ) ??
			$this->sitelinksValidator->validate( null, $serialization['sitelinks'], null, "$basePath/sitelinks" );
		if ( $validationError ) {
			return $validationError;
		}

		$this->deserializedItem = new Item(
			null,
			new Fingerprint(
				$this->labelsContentsValidator->getValidatedLabels(),
				$this->descriptionsContentsValidator->getValidatedDescriptions(),
				$this->aliasesValidator->getValidatedAliases()
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

	private function validateLabelsAndDescriptions( array $itemSerialization, string $basePath ): ?ValidationError {
		return $this->labelsSyntaxValidator->validate( $itemSerialization['labels'], "$basePath/labels" ) ??
			$this->descriptionsSyntaxValidator->validate( $itemSerialization['descriptions'], "$basePath/descriptions" ) ??
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
