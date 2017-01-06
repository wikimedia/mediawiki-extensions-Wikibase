<?php

namespace Wikibase\Lib;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\Lib\Store\PropertyInfoLookup;

/**
 * PropertyDataTypeLookup that uses an PropertyInfoLookup to find
 * a property's data type ID.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class PropertyInfoDataTypeLookup implements PropertyDataTypeLookup {

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $fallbackLookup;

	/**
	 * @var PropertyInfoLookup
	 */
	private $infoLookup;

	/**
	 * @param PropertyInfoLookup $infoLookup
	 * @param PropertyDataTypeLookup|null $fallbackLookup
	 */
	public function __construct( PropertyInfoLookup $infoLookup, PropertyDataTypeLookup $fallbackLookup = null ) {
		$this->infoLookup = $infoLookup;
		$this->fallbackLookup = $fallbackLookup;
	}

	/**
	 * @param PropertyId $propertyId
	 *
	 * @return string
	 * @throws PropertyDataTypeLookupException
	 */
	public function getDataTypeIdForProperty( PropertyId $propertyId ) {
		$dataTypeId = null;
		$info = $this->infoLookup->getPropertyInfo( $propertyId );

		if ( $info !== null && isset( $info[PropertyInfoLookup::KEY_DATA_TYPE] ) ) {
			$dataTypeId = $info[PropertyInfoLookup::KEY_DATA_TYPE];
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
			throw new PropertyDataTypeLookupException( $propertyId );
		}

		return $dataTypeId;
	}

}
