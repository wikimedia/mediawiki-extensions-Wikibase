<?php

namespace Wikibase;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;

/**
 * @deprecated
 * This class makes many assumptions that do not hold, including
 * - all entities can be constructed empty
 * - only Items and Properties exist
 * - all entities can construct themselves from their serialization
 * Not a single method is non-problematic, so you should not use this class at all.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class EntityFactory {

	/**
	 * @since 0.3
	 *
	 * @param string $entityType
	 *
	 * @throws OutOfBoundsException
	 * @return EntityDocument
	 */
	public function newEmpty( $entityType ) {
		switch ( $entityType ) {
			case 'item':
				return new Item();
			case 'property':
				return Property::newFromType( '' );
			default:
				throw new OutOfBoundsException( 'Unknown entity type ' . $entityType );
		}
	}

}
