<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemLabel;

use Wikibase\Repo\RestApi\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\LanguageCodeValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetItemLabelValidator {

	private ItemIdValidator $itemIdValidator;
	private LanguageCodeValidator $languageCodeValidator;

	public function __construct(
		ItemIdValidator $itemIdValidator,
		LanguageCodeValidator $languageCodeValidator
	) {
		$this->itemIdValidator = $itemIdValidator;
		$this->languageCodeValidator = $languageCodeValidator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function assertValidRequest( GetItemLabelRequest $request ): void {
		$this->validateItemId( $request->getItemId() );
		$this->validateLanguageCode( $request->getLanguageCode() );
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

}
