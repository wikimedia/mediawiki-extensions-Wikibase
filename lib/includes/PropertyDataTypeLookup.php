<?php

namespace Wikibase\Lib;

use Wikibase\EntityId;

/**
 * Interface for objects that can find the if of the DataType
 * for the Property of which the id is given.
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface PropertyDataTypeLookup {

	/**
	 * Returns the DataType for the Property of which the id is given.
	 *
	 * @since 0.4
	 *
	 * @param EntityId $propertyId
	 *
	 * @return string
	 * @throws PropertyNotFoundException
	 */
	public function getDataTypeIdForProperty( EntityId $propertyId );

}
