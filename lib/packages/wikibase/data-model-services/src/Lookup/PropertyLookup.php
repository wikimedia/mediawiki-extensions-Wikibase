<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * @since 1.0
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
interface PropertyLookup {

	/**
	 * @since 2.0
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return Property|null
	 * @throws PropertyLookupException
	 */
	public function getPropertyForId( PropertyId $propertyId );

}
