<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * PropertyLookup implementation providing a migration path away from
 * the EntityLookup interface.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LegacyAdapterPropertyLookup implements PropertyLookup {

	private $lookup;

	public function __construct( EntityLookup $lookup ) {
		$this->lookup = $lookup;
	}

	/**
	 * @param PropertyId $propertyId
	 *
	 * @return Property|null
	 * @throws PropertyLookupException
	 */
	public function getPropertyForId( PropertyId $propertyId ) {
		try {
			return $this->lookup->getEntity( $propertyId );
		} catch ( EntityLookupException $ex ) {
			throw new PropertyLookupException( $propertyId, $ex->getMessage(), $ex );
		}
	}

}
