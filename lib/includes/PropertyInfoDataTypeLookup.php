<?php

namespace Wikibase\Lib;

use InvalidArgumentException;
use Wikibase\EntityId;
use Wikibase\EntityLookup;
use Wikibase\Property;
use Wikibase\PropertyInfoStore;

/**
 * PropertyDataTypeLookup that uses an PropertyInfoStore to find
 * a property's data type ID.
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class PropertyInfoDataTypeLookup implements PropertyDataTypeLookup {

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $fallbackLookup;

	/**
	 * @var PropertyInfoStore
	 */
	private $infoStore;

	/**
	 * @param PropertyInfoStore $infoStore
	 * @param PropertyDataTypeLookup $fallbackLookup
	 */
	public function __construct( PropertyInfoStore $infoStore, PropertyDataTypeLookup $fallbackLookup = null ) {
		$this->infoStore = $infoStore;
		$this->fallbackLookup = $fallbackLookup;
	}

	/**
	 * @since 0.4
	 *
	 * @param EntityId $propertyId
	 *
	 * @return string
	 * @throws PropertyNotFoundException
	 */
	public function getDataTypeIdForProperty( EntityId $propertyId ) {
		$dataTypeId = null;
		$info = $this->infoStore->getPropertyInfo( $propertyId );

		if ( $info !== null && isset( $info[PropertyInfoStore::KEY_DATA_TYPE] ) ) {
			$dataTypeId = $info[PropertyInfoStore::KEY_DATA_TYPE];
		}

		if ( $dataTypeId === null && $this->fallbackLookup !== null ) {
			$dataTypeId = $this->fallbackLookup->getDataTypeIdForProperty( $propertyId );

			if ( $dataTypeId !== null ) {
				wfDebugLog( __CLASS__, __FUNCTION__ . ': No property info found for '
					. $propertyId . ', but property ID could be retrieved from fallback store!' );

				//TODO: Automatically update the info store?
				//TODO: Suggest to run rebuildPropertyInfo.php
			}
		}

		if ( $dataTypeId === null ) {
			throw new PropertyNotFoundException( $propertyId );
		}

		return $dataTypeId;
	}

}
