<?php

namespace Wikibase;

use Wikibase\DataModel\Entity\PropertyId;

/**
 * Removes a property info entry.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class PropertyInfoDeletion extends \DataUpdate {

	/**
	 * @param PropertyId $id
	 * @param PropertyInfoStore $store
	 */
	public function __construct( PropertyId $id, PropertyInfoStore $store ) {
		$this->propertyId = $id;
		$this->store = $store;
	}

	/**
	 * Perform the actual work
	 */
	public function doUpdate() {
		wfDebugLog( __CLASS__, __FUNCTION__ . ': deleting property info for ' . $this->propertyId );
		$this->store->removePropertyInfo( $this->propertyId );
	}

}
