<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemStatements;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyIdValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetItemStatementsValidator {

	private ItemIdValidator $itemIdValidator;
	private PropertyIdValidator $propertyIdValidator;

	public function __construct(
		ItemIdValidator $itemIdValidator,
		PropertyIdValidator $propertyIdValidator
	) {
		$this->itemIdValidator = $itemIdValidator;
		$this->propertyIdValidator = $propertyIdValidator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function assertValidRequest( GetItemStatementsRequest $request ): void {
		$validationError = $this->itemIdValidator->validate( $request->getItemId() );
		if ( $validationError ) {
			throw new UseCaseError(
				UseCaseError::INVALID_ITEM_ID,
				"Not a valid item ID: {$validationError->getContext()[ItemIdValidator::CONTEXT_VALUE]}"
			);
		}

		$this->validatePropertyId( $request->getStatementPropertyId() );
	}

	/**
	 * @throws UseCaseError
	 */
	private function validatePropertyId( ?string $propertyId ): void {
		if ( $propertyId === null ) {
			return;
		}

		$validationError = $this->propertyIdValidator->validate( $propertyId );
		if ( $validationError ) {
			$context = $validationError->getContext();
			throw new UseCaseError(
				UseCaseError::INVALID_PROPERTY_ID,
				"Not a valid property ID: {$context[PropertyIdValidator::CONTEXT_VALUE]}",
				[ UseCaseError::CONTEXT_PROPERTY_ID => $context[PropertyIdValidator::CONTEXT_VALUE] ]
			);
		}
	}

}
