<?php declare( strict_types=1 );

namespace Wikibase\Repo\GraphQLPrototype;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;

/**
 * @license GPL-2.0-or-later
 */
class ItemResolver {
	public function __construct( private EntityLookup $entityLookup ) {
	}

	/**
	 * @throws InvalidItemId
	 * @throws ItemNotFound
	 */
	public function fetchItem( string $itemId ): array {
		try {
			$parsedId = new ItemId( $itemId );
		} catch ( InvalidArgumentException ) {
			throw new InvalidItemId( "Invalid Item ID: '$itemId'." );
		}

		if ( !$this->entityLookup->hasEntity( $parsedId ) ) {
			throw new ItemNotFound( "Item '$parsedId' does not exist." );
		}

		return [ 'id' => $parsedId->getSerialization() ];
	}
}
