<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Validation;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @license GPL-2.0-or-later
 */
class ItemIdValidator {

	public const CODE_INVALID = 'invalid-item-id';
	public const CONTEXT_VALUE = 'item-id-value';

	public function validate( string $itemId ): ?ValidationError {
		try {
			// @phan-suppress-next-line PhanNoopNew
			new ItemId( $itemId );
		} catch ( InvalidArgumentException $ex ) {
			return new ValidationError( self::CODE_INVALID, [ self::CONTEXT_VALUE => $itemId ] );
		}
		return null;
	}

}
