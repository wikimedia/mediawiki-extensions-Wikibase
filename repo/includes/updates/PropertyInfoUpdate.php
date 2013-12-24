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
		$cry = false;
		foreach( debug_backtrace() as $step ) {
			if ( $step['function'] == 'testRebuildPropertyInfo' ) {
				$cry = true;
				break;
			}
		}
		if ( $cry ) echo "PropertyInfoUpdate-__construct-0\n";
		$this->property = $property;
		$this->store = $store;
		if ( $cry ) echo "PropertyInfoUpdate-__construct-1\n";
	}

	/**
	 * Perform the actual work
	 */
	function doUpdate() {
		$cry = false;
		foreach( debug_backtrace() as $step ) {
			if ( $step['function'] == 'testRebuildPropertyInfo' ) {
				$cry = true;
				break;
			}
		}
		//XXX: Where to encode the knowledge about how to extract an info array from a Property object?
		//     Should we have a PropertyInfo class? Or can we put this into the Property class?
if ( $cry ) echo "PropertyInfoUpdate-doUpdate-0\n";
		$info = array(
			PropertyInfoStore::KEY_DATA_TYPE => $this->property->getDataTypeId()
		);
if ( $cry ) echo "PropertyInfoUpdate-doUpdate-1\n";
		$id = $this->property->getId();if ( $cry ) echo "PropertyInfoUpdate-doUpdate-2\n";
		$oldInfo = $this->store->getPropertyInfo( $id );
if ( $cry ) echo "PropertyInfoUpdate-doUpdate-3\n";
		if ( $oldInfo !== $info ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ': property info for ' . $id . ' changed, updating' );
			if ( $cry ) echo "PropertyInfoUpdate-doUpdate-setPropertyInfo-0\n";
			$this->store->setPropertyInfo( $id, $info );
			if ( $cry ) echo "PropertyInfoUpdate-doUpdate-setPropertyInfo-1\n";
		} else {
			if ( $cry ) echo "PropertyInfoUpdate-doUpdate-notSetPropertyInfo\n";
			wfDebugLog( __CLASS__, __FUNCTION__ . ': property info for ' . $id . ' didn\'t change, skipping update' );
		}
	}

}
