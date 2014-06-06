<?php

namespace Wikibase\DataModel\Entity;

/**
 * @since 1.0
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface PropertyDataTypeLookup {

	/**
	 * Returns the data type for the Property of which the id is given.
	 *
	 * @since 0.4
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return string
	 * @throws PropertyNotFoundException
	 */
	public function getDataTypeIdForProperty( PropertyId $propertyId );

}
