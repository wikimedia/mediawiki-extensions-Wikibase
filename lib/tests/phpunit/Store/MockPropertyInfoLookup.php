<?php

namespace Wikibase\Lib\Tests\Store;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Edrsf\PropertyInfoLookup;

/**
 * Class MockPropertyInfoLockup is an implementation of PropertyInfoLookup based on a local array.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class MockPropertyInfoLookup implements PropertyInfoLookup {

	/**
	 * Maps properties to info arrays
	 *
	 * @var array[]
	 */
	protected $propertyInfo = array();

	/**
	 * @param array $info Array mapping properties (id serialization) to info arrays
	 */
	public function __construct( array $info = [] ) {
		$this->propertyInfo = $info;
	}

	/**
	 * @see \Wikibase\Edrsf\PropertyInfoLookup::getPropertyInfo
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return array|null
	 * @throws InvalidArgumentException
	 */
	public function getPropertyInfo( PropertyId $propertyId ) {
		$propertyInfo = $this->getAllPropertyInfo();
		$id = $propertyId->getSerialization();

		if ( isset( $propertyInfo[$id] ) ) {
			return $propertyInfo[$id];
		}

		return null;
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
		$propertyInfoForDataType = array();

		foreach ( $propertyInfo as $id => $info ) {
			if ( $info[PropertyInfoLookup::KEY_DATA_TYPE] === $dataType ) {
				$propertyInfoForDataType[$id] = $info;
			}
		}

		return $propertyInfoForDataType;
	}

	/**
	 * @see \Wikibase\Edrsf\PropertyInfoLookup::getAllPropertyInfo
	 *
	 * @return array[]
	 */
	public function getAllPropertyInfo() {
		return $this->propertyInfo;
	}

	/**
	 * @param PropertyId $propertyId
	 * @param array $info
	 */
	public function addPropertyInfo( PropertyId $propertyId, array $info ) {
		$id = $propertyId->getSerialization();
		$this->propertyInfo[$id] = $info;
	}

}
