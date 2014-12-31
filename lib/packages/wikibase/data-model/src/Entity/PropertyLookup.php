<?php

namespace Wikibase\DataModel\Entity;

/**
 * @since 2.5
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
interface PropertyLookup {

	/**
	 * Returns the Property of which the id is given.
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return Property
	 * @throws PropertyNotFoundException
	 */
	public function getPropertyForId( PropertyId $propertyId );

}
