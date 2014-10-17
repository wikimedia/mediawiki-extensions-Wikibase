<?php

namespace Wikibase\DataModel;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * Turns legacy entity id serializations consisting of entity type + numeric id
 * into present day EntityId implementations.
 *
 * New usages of this class should be very carefully considered.
 * This class is internal to DataModel and should not be used by other components.
 *
 * @since 1.0
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com
 */
class LegacyIdInterpreter {

	/**
	 * @param string $entityType
	 * @param int|string $numericId
	 *
	 * @return EntityId
	 * @throws InvalidArgumentException
	 */
	public static function newIdFromTypeAndNumber( $entityType, $numericId ) {
		if ( $entityType === 'item' ) {
			return ItemId::newFromNumber( $numericId );
		} elseif ( $entityType === 'property' ) {
			return PropertyId::newFromNumber( $numericId );
		}

		throw new InvalidArgumentException( 'Invalid entityType ' . $entityType );
	}

}
