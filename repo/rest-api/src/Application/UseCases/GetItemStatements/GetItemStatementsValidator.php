<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemStatements;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetItemStatementsValidator {

	public const CODE_INVALID_PROPERTY_ID = 'invalid-property-id';
	public const CONTEXT_PROPERTY_ID_VALUE = 'property-id-value';

	private ItemIdValidator $itemIdValidator;

	public function __construct( ItemIdValidator $itemIdValidator ) {
		$this->itemIdValidator = $itemIdValidator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function assertValidRequest( GetItemStatementsRequest $request ): void {
		$validationError = $this->itemIdValidator->validate( $request->getItemId() );
		if ( $validationError ) {
			throw new UseCaseError(
				UseCaseError::INVALID_ITEM_ID,
				'Not a valid item ID: ' . $validationError->getContext()[ItemIdValidator::CONTEXT_VALUE]
			);
		}

		$this->validatePropertyId( $request->getStatementPropertyId() );
	}

	/**
	 * @throws UseCaseError
	 */
	private function validatePropertyId( ?string $propertyId ): void {
		if ( !$propertyId ) {
			return;
		}

		try {
			// @phan-suppress-next-line PhanNoopNew
			new NumericPropertyId( $propertyId );
		} catch ( InvalidArgumentException $e ) {
			throw new UseCaseError(
				UseCaseError::INVALID_PROPERTY_ID,
				"Not a valid property ID: {$propertyId}",
				[ self::CONTEXT_PROPERTY_ID_VALUE => $propertyId ]
			);
		}
	}

}
