<?php


namespace Wikibase;

use Wikibase\DataModel\Entity\Property;

/**
 * Class to build the information about a property.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class PropertyInfoBuilder {

	/**
	 * @param Property $property
	 * @return array
	 */
	public function buildPropertyInfo( Property $property ) {
		return array(
			PropertyInfoStore::KEY_DATA_TYPE => $property->getDataTypeId()
		);
	}

}
