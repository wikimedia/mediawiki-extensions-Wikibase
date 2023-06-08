<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\NumericPropertyId;

/**
 * @license GPL-2.0-or-later
 */
class PropertyIdValidator {

	public const CODE_INVALID = 'invalid-property-id';
	public const CONTEXT_VALUE = 'property-id-value';

	public function validate( string $propertyId ): ?ValidationError {
		try {
			// @phan-suppress-next-line PhanNoopNew
			new NumericPropertyId( $propertyId );
		} catch ( InvalidArgumentException $ex ) {
			return new ValidationError( self::CODE_INVALID, [ self::CONTEXT_VALUE => $propertyId ] );
		}
		return null;
	}

}
