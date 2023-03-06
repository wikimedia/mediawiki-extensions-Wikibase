<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemAliases;

use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetItemAliasesValidator {

	private ItemIdValidator $itemIdValidator;

	public function __construct( ItemIdValidator $itemIdValidator ) {
		$this->itemIdValidator = $itemIdValidator;
	}

	/**
	 * @throws UseCaseException
	 */
	public function assertValidRequest( GetItemAliasesRequest $request ): void {
		$validationError = $this->itemIdValidator->validate( $request->getItemId() );

		if ( $validationError ) {
			throw new UseCaseException(
				ErrorResponse::INVALID_ITEM_ID,
				'Not a valid item ID: ' . $validationError->getContext()[ItemIdValidator::CONTEXT_VALUE]
			);
		}
	}
}
