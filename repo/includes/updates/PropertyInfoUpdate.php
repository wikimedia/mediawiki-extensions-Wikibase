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
echo "PropertyInfoUpdate-doUpdate-0\n";
		$info = array(
			PropertyInfoStore::KEY_DATA_TYPE => $this->property->getDataTypeId()
		);
echo "PropertyInfoUpdate-doUpdate-1\n";
		$id = $this->property->getId();echo "PropertyInfoUpdate-doUpdate-2\n";
		$oldInfo = $this->store->getPropertyInfo( $id );
echo "PropertyInfoUpdate-doUpdate-3\n";
		if ( $oldInfo !== $info ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ': property info for ' . $id . ' changed, updating' );
			echo "PropertyInfoUpdate-doUpdate-setPropertyInfo-0\n";
			$this->store->setPropertyInfo( $id, $info );
			echo "PropertyInfoUpdate-doUpdate-setPropertyInfo-1\n";
		} else {
			echo "PropertyInfoUpdate-doUpdate-notSetPropertyInfo\n";
			wfDebugLog( __CLASS__, __FUNCTION__ . ': property info for ' . $id . ' didn\'t change, skipping update' );
		}
	}

}
