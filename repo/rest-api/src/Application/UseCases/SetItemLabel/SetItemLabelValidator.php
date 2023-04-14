<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel;

use LogicException;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\LabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;

/**
 * @license GPL-2.0-or-later
 */
class SetItemLabelValidator {

	private ItemIdValidator $itemIdValidator;
	private LanguageCodeValidator $languageCodeValidator;
	private EditMetadataValidator $editMetadataValidator;
	private LabelValidator $labelValidator;

	public function __construct(
		ItemIdValidator $itemIdValidator,
		LanguageCodeValidator $languageCodeValidator,
		EditMetadataValidator $editMetadataValidator,
		LabelValidator $labelValidator
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
		$this->validateLabel( $request->getLabel() );
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
	private function validateLabel( string $label ): void {
		$validationError = $this->labelValidator->validate( $label );
		if ( $validationError ) {
			$errorCode = $validationError->getCode();
			$context = $validationError->getContext();

			switch ( $errorCode ) {
				case LabelValidator::LABEL_EMPTY:
					throw new UseCaseError(
						UseCaseError::LABEL_EMPTY,
						'Label must not be empty'
					);
				case LabelValidator::LABEL_TOO_LONG:
					$maxLabelLength = $context[LabelValidator::CONTEXT_LIMIT];
					throw new UseCaseError(
						UseCaseError::LABEL_TOO_LONG,
						"Label must be no more than $maxLabelLength characters long",
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
