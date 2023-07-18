<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels;

use LogicException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\Serialization\EmptyLabelException;
use Wikibase\Repo\RestApi\Application\Serialization\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;

/**
 * @license GPL-2.0-or-later
 */
class PatchedLabelsValidator {

	public const CONTEXT_LANGUAGE = 'language';
	public const CONTEXT_LABEL = 'label';
	public const CONTEXT_VALUE = 'value';
	public const CONTEXT_LIMIT = 'character-limit';
	public const CONTEXT_DESCRIPTION = 'description';
	public const CONTEXT_MATCHING_ITEM_ID = 'matching-item-id';

	private LabelsDeserializer $labelsDeserializer;
	private ItemLabelValidator $labelValidator;
	private LanguageCodeValidator $languageCodeValidator;

	public function __construct(
		LabelsDeserializer $labelsDeserializer,
		ItemLabelValidator $labelValidator,
		LanguageCodeValidator $languageCodeValidator
	) {
		$this->labelsDeserializer = $labelsDeserializer;
		$this->labelValidator = $labelValidator;
		$this->languageCodeValidator = $languageCodeValidator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( ItemId $itemId, TermList $originalLabels, array $labelsSerialization ): TermList {
		$patchedLabels = $this->deserializeLabels( $labelsSerialization );

		foreach ( $this->getModifiedLabels( $originalLabels, $patchedLabels ) as $label ) {
			$this->validateLabel( $itemId, $label );
			$this->validateLanguageCode( $label );
		}

		return $patchedLabels;
	}

	private function deserializeLabels( array $labelsSerialization ): TermList {
		try {
			$labels = $this->labelsDeserializer->deserialize( $labelsSerialization );
		} catch ( EmptyLabelException $e ) {
			$languageCode = $e->getField();
			throw new UseCaseError(
				UseCaseError::PATCHED_LABEL_EMPTY,
				"Changed label for '$languageCode' cannot be empty",
				[ self::CONTEXT_LANGUAGE => $languageCode ]
			);
		} catch ( InvalidFieldException $e ) {
			$languageCode = $e->getField();
			$invalidLabel = json_encode( $e->getValue() );
			throw new UseCaseError(
				UseCaseError::PATCHED_LABEL_INVALID,
				"Changed label for '$languageCode' is invalid: $invalidLabel",
				[
					self::CONTEXT_LANGUAGE => $languageCode,
					self::CONTEXT_VALUE => $invalidLabel,
				]
			);
		}

		return $labels;
	}

	private function getModifiedLabels( TermList $original, TermList $modified ): array {
		$modifiedLabels = [];

		foreach ( $modified as $label ) {
			if ( !$original->hasTermForLanguage( $label->getLanguageCode() ) ) {
				$modifiedLabels[] = $label;
			} elseif ( $original->getByLanguage( $label->getLanguageCode() )->getText() != $label->getText() ) {
				$modifiedLabels[] = $label;
			}
		}

		return $modifiedLabels;
	}

	private function validateLabel( ItemId $itemId, Term $label ): void {
		$validationError = $this->labelValidator->validate( $itemId, $label->getLanguageCode(), $label->getText() );
		if ( !$validationError ) {
			return;
		}

		$context = $validationError->getContext();
		switch ( $validationError->getCode() ) {
			case ItemLabelValidator::CODE_INVALID:
				throw new UseCaseError(
					UseCaseError::PATCHED_LABEL_INVALID,
					"Changed label for '{$label->getLanguageCode()}' is invalid: {$label->getText()}",
					[
						self::CONTEXT_LANGUAGE => $label->getLanguageCode(),
						self::CONTEXT_VALUE => $label->getText(),
					]
				);
			case ItemLabelValidator::CODE_TOO_LONG:
				$maxLabelLength = $context[ItemLabelValidator::CONTEXT_LIMIT];
				throw new UseCaseError(
					UseCaseError::PATCHED_LABEL_TOO_LONG,
					"Changed label for '{$label->getLanguageCode()}' must not be more than $maxLabelLength characters long",
					[
						self::CONTEXT_LANGUAGE => $label->getLanguageCode(),
						self::CONTEXT_VALUE => $context[ItemLabelValidator::CONTEXT_VALUE],
						self::CONTEXT_LIMIT => $context[ItemLabelValidator::CONTEXT_LIMIT],
					]
				);
			case ItemLabelValidator::CODE_LABEL_DESCRIPTION_DUPLICATE:
				$languageCode = $context[ItemLabelValidator::CONTEXT_LANGUAGE];
				$label = $context[ItemLabelValidator::CONTEXT_LABEL];
				$duplicateItemId = $context[ItemLabelValidator::CONTEXT_MATCHING_ITEM_ID];
				throw new UseCaseError(
					UseCaseError::PATCHED_ITEM_LABEL_DESCRIPTION_DUPLICATE,
					"Item $duplicateItemId already has label '$label' associated with language " .
					"code $languageCode, using the same description text.",
					[
						self::CONTEXT_LANGUAGE => $context[ItemLabelValidator::CONTEXT_LANGUAGE],
						self::CONTEXT_LABEL => $context[ItemLabelValidator::CONTEXT_LABEL],
						self::CONTEXT_DESCRIPTION => $context[ItemLabelValidator::CONTEXT_DESCRIPTION],
						self::CONTEXT_MATCHING_ITEM_ID => $context[ItemLabelValidator::CONTEXT_MATCHING_ITEM_ID],
					]
				);
			case ItemLabelValidator::CODE_LABEL_DESCRIPTION_EQUAL:
				$language = $context[ItemLabelValidator::CONTEXT_LANGUAGE];
				throw new UseCaseError(
					UseCaseError::PATCHED_ITEM_LABEL_DESCRIPTION_SAME_VALUE,
					"Label and description for language code {$language} can not have the same value.",
					[ self::CONTEXT_LANGUAGE => $context[ItemLabelValidator::CONTEXT_LANGUAGE] ]
				);
			default:
				throw new LogicException( "Unknown validation error: {$validationError->getCode()}" );
		}
	}

	private function validateLanguageCode( Term $label ): void {
		$validationError = $this->languageCodeValidator->validate( $label->getLanguageCode() );
		if ( $validationError ) {
			$languageCode = $validationError->getContext()[LanguageCodeValidator::CONTEXT_LANGUAGE_CODE_VALUE];
			throw new UseCaseError(
				UseCaseError::PATCHED_LABEL_INVALID_LANGUAGE_CODE,
				"Not a valid language code '$languageCode' in changed labels",
				[ self::CONTEXT_LANGUAGE => $languageCode ]
			);
		}
	}

}
