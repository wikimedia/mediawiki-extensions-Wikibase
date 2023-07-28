<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription;

use LogicException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\DescriptionLanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemDescriptionValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;

/**
 * @license GPL-2.0-or-later
 */
class SetItemDescriptionValidator {

	private ItemIdValidator $itemIdValidator;
	private DescriptionLanguageCodeValidator $languageCodeValidator;
	private ItemDescriptionValidator $itemDescriptionValidator;
	private EditMetadataValidator $editMetadataValidator;

	public function __construct(
		ItemIdValidator $itemIdValidator,
		DescriptionLanguageCodeValidator $languageCodeValidator,
		ItemDescriptionValidator $itemDescriptionValidator,
		EditMetadataValidator $editMetadataValidator
	) {
		$this->itemIdValidator = $itemIdValidator;
		$this->languageCodeValidator = $languageCodeValidator;
		$this->itemDescriptionValidator = $itemDescriptionValidator;
		$this->editMetadataValidator = $editMetadataValidator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function assertValidRequest( SetItemDescriptionRequest $request ): void {
		$this->validateItemId( $request->getItemId() );
		$this->validateLanguageCode( $request->getLanguageCode() );
		$this->validateDescription(
			new ItemId( $request->getItemId() ),
			$request->getLanguageCode(),
			$request->getDescription()
		);
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
				"Not a valid item ID: {$validationError->getContext()[ItemIdValidator::CONTEXT_VALUE]}"
			);
		}
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateLanguageCode( string $language ): void {
		$validationError = $this->languageCodeValidator->validate( $language );
		if ( $validationError ) {
			$languageCode = $validationError->getContext()[DescriptionLanguageCodeValidator::CONTEXT_LANGUAGE];
			throw new UseCaseError(
				UseCaseError::INVALID_LANGUAGE_CODE,
				"Not a valid language code: $languageCode"
			);
		}
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateDescription( ItemId $itemId, string $language, string $description ): void {
		$validationError = $this->itemDescriptionValidator->validate( $itemId, $language, $description );
		if ( $validationError ) {
			$errorCode = $validationError->getCode();
			$context = $validationError->getContext();
			switch ( $errorCode ) {
				case ItemDescriptionValidator::CODE_INVALID:
					throw new UseCaseError(
						UseCaseError::INVALID_DESCRIPTION,
						"Not a valid description: {$context[ItemDescriptionValidator::CONTEXT_VALUE]}"
					);
				case ItemDescriptionValidator::CODE_EMPTY:
					throw new UseCaseError(
						UseCaseError::DESCRIPTION_EMPTY,
						'Description must not be empty'
					);
				case ItemDescriptionValidator::CODE_TOO_LONG:
					$limit = $context[ItemDescriptionValidator::CONTEXT_LIMIT];
					throw new UseCaseError(
						UseCaseError::DESCRIPTION_TOO_LONG,
						"Description must be no more than $limit characters long",
						[
							UseCaseError::CONTEXT_VALUE => $context[ItemDescriptionValidator::CONTEXT_VALUE],
							UseCaseError::CONTEXT_CHARACTER_LIMIT => $limit,
						]
					);
				case ItemDescriptionValidator::CODE_LABEL_DESCRIPTION_EQUAL:
					throw new UseCaseError(
						UseCaseError::LABEL_DESCRIPTION_SAME_VALUE,
						"Label and description for language code '$language' can not have the same value",
						[ UseCaseError::CONTEXT_LANGUAGE => $context[ItemDescriptionValidator::CONTEXT_LANGUAGE] ]
					);
				case ItemDescriptionValidator::CODE_LABEL_DESCRIPTION_DUPLICATE:
					$matchingItemId = $context[ItemDescriptionValidator::CONTEXT_MATCHING_ITEM_ID];
					$label = $context[ItemDescriptionValidator::CONTEXT_LABEL];
					throw new UseCaseError(
						UseCaseError::ITEM_LABEL_DESCRIPTION_DUPLICATE,
						"Item '$matchingItemId' already has label '$label' associated with "
						. "language code '$language', using the same description text",
						[
							UseCaseError::CONTEXT_LANGUAGE => $context[ItemDescriptionValidator::CONTEXT_LANGUAGE],
							UseCaseError::CONTEXT_LABEL => $label,
							UseCaseError::CONTEXT_DESCRIPTION => $context[ItemDescriptionValidator::CONTEXT_DESCRIPTION],
							UseCaseError::CONTEXT_MATCHING_ITEM_ID => $matchingItemId,
						]
					);
				default:
					throw new LogicException( "Unexpected validation error code: $errorCode" );
			}
		}
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateEditTags( array $editTags ): void {
		$validationError = $this->editMetadataValidator->validateEditTags( $editTags );
		if ( $validationError ) {
			throw new UseCaseError(
				UseCaseError::INVALID_EDIT_TAG,
				"Invalid MediaWiki tag: {$validationError->getContext()[EditMetadataValidator::CONTEXT_TAG_VALUE]}"
			);
		}
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateComment( ?string $comment ): void {
		if ( $comment === null ) {
			return;
		}

		$validationError = $this->editMetadataValidator->validateComment( $comment );
		if ( $validationError ) {
			$commentMaxLength = $validationError->getContext()[EditMetadataValidator::CONTEXT_COMMENT_MAX_LENGTH];
			throw new UseCaseError(
				UseCaseError::COMMENT_TOO_LONG,
				"Comment must not be longer than $commentMaxLength characters.",
			);
		}
	}

}
