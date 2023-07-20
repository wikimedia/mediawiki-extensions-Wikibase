<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemDescriptions;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetItemDescriptionsValidator {

	private ItemIdValidator $itemIdValidator;

	public function __construct( ItemIdValidator $itemIdValidator ) {
		$this->itemIdValidator = $itemIdValidator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function assertValidRequest( GetItemDescriptionsRequest $request ): void {
		$validationError = $this->itemIdValidator->validate( $request->getItemId() );
		if ( $validationError ) {
			throw new UseCaseError(
				UseCaseError::INVALID_ITEM_ID,
				"Not a valid item ID: {$validationError->getContext()[ItemIdValidator::CONTEXT_VALUE]}"
			);
		}
	}
}
