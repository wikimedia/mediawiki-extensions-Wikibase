<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Validation;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @license GPL-2.0-or-later
 */
class ItemIdValidator {

	public function validate( string $itemId, string $source ): ?ValidationError {
		try {
			// @phan-suppress-next-line PhanNoopNew
			new ItemId( $itemId );
		} catch ( InvalidArgumentException $ex ) {
			return new ValidationError( $itemId, $source );
		}
		return null;
	}

}
