<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemStatements;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;

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
	 * @throws UseCaseException
	 */
	public function assertValidRequest( GetItemStatementsRequest $request ): void {
		$validationError = $this->itemIdValidator->validate( $request->getItemId() );
		if ( $validationError ) {
			throw new UseCaseException(
				ErrorResponse::INVALID_ITEM_ID,
				'Not a valid item ID: ' . $validationError->getContext()[ItemIdValidator::CONTEXT_VALUE]
			);
		}

		$this->validatePropertyId( $request->getStatementPropertyId() );
	}

	/**
	 * @throws UseCaseException
	 */
	private function validatePropertyId( ?string $propertyId ): void {
		if ( !$propertyId ) {
			return;
		}

		try {
			// @phan-suppress-next-line PhanNoopNew
			new NumericPropertyId( $propertyId );
		} catch ( InvalidArgumentException $e ) {
			throw new UseCaseException(
				ErrorResponse::INVALID_PROPERTY_ID,
				"Not a valid property ID: {$propertyId}",
				[ self::CONTEXT_PROPERTY_ID_VALUE => $propertyId ]
			);
		}
	}

}
