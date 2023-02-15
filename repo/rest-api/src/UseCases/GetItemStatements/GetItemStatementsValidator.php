<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemStatements;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

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

	public function validate( GetItemStatementsRequest $request ): ?ValidationError {
		return $this->itemIdValidator->validate( $request->getItemId() )
			?: $this->validatePropertyId( $request->getStatementPropertyId() );
	}

	private function validatePropertyId( ?string $propertyId ): ?ValidationError {
		if ( !$propertyId ) {
			return null;
		}

		try {
			// @phan-suppress-next-line PhanNoopNew
			new NumericPropertyId( $propertyId );
			return null;
		} catch ( InvalidArgumentException $e ) {
			return new ValidationError(
				self::CODE_INVALID_PROPERTY_ID,
				[ self::CONTEXT_PROPERTY_ID_VALUE => $propertyId ]
			);
		}
	}

}
