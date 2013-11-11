<?php

namespace Wikibase;

/**
 * Updates property info entries.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class PropertyInfoUpdate extends \DataUpdate {

	protected $property;
	protected $store;

	/**
	 * @param Property $property
	 * @param PropertyInfoStore $store
	 */
	public function __construct( Property $property, PropertyInfoStore $store ) {
		$this->property = $property;
		$this->store = $store;
	}

	/**
	 * Perform the actual work
	 */
	function doUpdate() {
		//XXX: Where to encode the knowledge about how to extract an info array from a Property object?
		//     Should we have a PropertyInfo class? Or can we put this into the Property class?

		$info = array(
			PropertyInfoStore::KEY_DATA_TYPE => $this->property->getDataTypeId()
		);

		$id = $this->property->getId();
		$oldInfo = $this->store->getPropertyInfo( $id );

		if ( $oldInfo !== $info ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ': property info for ' . $id . ' changed, updating' );
			$this->store->setPropertyInfo( $id, $info );
		} else {
			wfDebugLog( __CLASS__, __FUNCTION__ . ': property info for ' . $id . ' didn\'t change, skipping update' );
		}
	}

}
