<?php

namespace Wikibase\Lib\Tests\Store;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\PropertyInfoLookup;

/**
 * Class MockPropertyInfoLockup is an implementation of PropertyInfoLookup based on a local array.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class MockPropertyInfoLookup implements PropertyInfoLookup {

	/**
	 * Maps properties to info arrays
	 *
	 * @var array[]
	 */
	protected $propertyInfo = [];

	/**
	 * @param array $info Array mapping properties (id serialization) to info arrays
	 */
	public function __construct( array $info = [] ) {
		$this->propertyInfo = $info;
	}

	/**
	 * @see PropertyInfoLookup::getPropertyInfo
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return array|null
	 * @throws InvalidArgumentException
	 */
	public function getPropertyInfo( PropertyId $propertyId ) {
		$propertyInfo = $this->getAllPropertyInfo();
		$id = $propertyId->getSerialization();
		return $propertyInfo[$id] ?? null;
	}

	/**
	 * @see PropertyInfoLookup::getPropertyInfoForDataType
	 *
	 * @param string $dataType
	 *
	 * @return array[]
	 */
	public function getPropertyInfoForDataType( $dataType ) {
		$propertyInfo = $this->getAllPropertyInfo();
		$propertyInfoForDataType = [];

		foreach ( $propertyInfo as $id => $info ) {
			if ( $info[PropertyInfoLookup::KEY_DATA_TYPE] === $dataType ) {
				$propertyInfoForDataType[$id] = $info;
			}
		}

		return $propertyInfoForDataType;
	}

	/**
	 * @see PropertyInfoLookup::getAllPropertyInfo
	 *
	 * @return array[]
	 */
	public function getAllPropertyInfo() {
		return $this->propertyInfo;
	}

	/**
	 * @param NumericPropertyId $propertyId
	 * @param array $info
	 */
	public function addPropertyInfo( NumericPropertyId $propertyId, array $info ) {
		$id = $propertyId->getSerialization();
		$this->propertyInfo[$id] = $info;
	}

}
