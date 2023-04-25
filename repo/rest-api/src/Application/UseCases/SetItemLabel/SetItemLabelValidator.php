<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel;

use LogicException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;

/**
 * @license GPL-2.0-or-later
 */
class SetItemLabelValidator {

	private ItemIdValidator $itemIdValidator;
	private LanguageCodeValidator $languageCodeValidator;
	private EditMetadataValidator $editMetadataValidator;
	private ItemLabelValidator $labelValidator;

	public function __construct(
		ItemIdValidator $itemIdValidator,
		LanguageCodeValidator $languageCodeValidator,
		EditMetadataValidator $editMetadataValidator,
		ItemLabelValidator $labelValidator
	) {
		$this->itemIdValidator = $itemIdValidator;
		$this->languageCodeValidator = $languageCodeValidator;
		$this->editMetadataValidator = $editMetadataValidator;
		$this->labelValidator = $labelValidator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function assertValidRequest( SetItemLabelRequest $request ): void {
		$this->validateItemId( $request->getItemId() );
		$this->validateLanguageCode( $request->getLanguageCode() );
		$this->validateLabel( $request->getItemId(), $request->getLanguageCode(), $request->getLabel() );
		$this->validateEditTags( $request->getEditTags() );
		$this->validateComment( $request->getComment() );
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateItemId( string $itemId ): void {
		$validationError = $this->itemIdValidator->validate( $itemId );

		if ( $validationError ) {
			throw new UseCaseError(
				UseCaseError::INVALID_ITEM_ID,
				'Not a valid item ID: ' . $validationError->getContext()[ItemIdValidator::CONTEXT_VALUE]
			);
		}
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateLanguageCode( string $languageCode ): void {
		$validationError = $this->languageCodeValidator->validate( $languageCode );

		if ( $validationError ) {
			throw new UseCaseError(
				UseCaseError::INVALID_LANGUAGE_CODE,
				'Not a valid language code: ' . $validationError->getContext()[LanguageCodeValidator::CONTEXT_LANGUAGE_CODE_VALUE]
			);
		}
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateLabel( string $itemId, string $languageCode, string $label ): void {
		$itemId = new ItemId( $itemId );
		$validationError = $this->labelValidator->validate( $itemId, $languageCode, $label );
		if ( $validationError ) {
			$errorCode = $validationError->getCode();
			$context = $validationError->getContext();

			switch ( $errorCode ) {
				case ItemLabelValidator::CODE_INVALID:
					throw new UseCaseError(
						UseCaseError::INVALID_LABEL,
						"Not a valid label: {$context[ItemLabelValidator::CONTEXT_VALUE]}"
					);
				case ItemLabelValidator::CODE_EMPTY:
					throw new UseCaseError(
						UseCaseError::LABEL_EMPTY,
						'Label must not be empty'
					);
				case ItemLabelValidator::CODE_TOO_LONG:
					$maxLabelLength = $context[ItemLabelValidator::CONTEXT_LIMIT];
					throw new UseCaseError(
						UseCaseError::LABEL_TOO_LONG,
						"Label must be no more than $maxLabelLength characters long",
						$context
					);
				case ItemLabelValidator::CODE_LABEL_DESCRIPTION_EQUAL:
					$language = $context[ItemLabelValidator::CONTEXT_LANGUAGE];
					throw new UseCaseError(
						UseCaseError::LABEL_DESCRIPTION_SAME_VALUE,
						"Label and description for language code '$language' can not have the same value.",
						$context
					);
				case ItemLabelValidator::CODE_LABEL_DESCRIPTION_DUPLICATE:
					$language = $context[ItemLabelValidator::CONTEXT_LANGUAGE];
					$matchingItemId = $context[ItemLabelValidator::CONTEXT_MATCHING_ITEM_ID];
					$label = $context[ItemLabelValidator::CONTEXT_LABEL];
					throw new UseCaseError(
						UseCaseError::ITEM_LABEL_DESCRIPTION_DUPLICATE,
						"Item $matchingItemId already has label '$label' associated with " .
						"language code '$language', using the same description text.",
						$context
					);
				default:
					throw new LogicException( "Unknown validation error code: $errorCode" );
			}
		}
	}

	private function validateEditTags( array $editTags ): void {
		$validationError = $this->editMetadataValidator->validateEditTags( $editTags );

		if ( $validationError ) {
			throw new UseCaseError(
				UseCaseError::INVALID_EDIT_TAG,
				"Invalid MediaWiki tag: {$validationError->getContext()[EditMetadataValidator::CONTEXT_TAG_VALUE]}"
			);
		}
	}

	private function validateComment( ?string $comment ): void {
		$validationError = $this->editMetadataValidator->validateComment( $comment );

		if ( $validationError ) {
			$commentMaxLength = $validationError->getContext()[ EditMetadataValidator::CONTEXT_COMMENT_MAX_LENGTH ];
			throw new UseCaseError(
				UseCaseError::COMMENT_TOO_LONG,
				"Comment must not be longer than $commentMaxLength characters."
			);
		}
	}

}
