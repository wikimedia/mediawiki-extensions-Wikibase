<?php

namespace Wikibase\Lib\Store;

use DBError;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\PropertyInfoStore;

/**
 * Delegates read operations to a PropertyInfoLookup configured for
 * the repository the input PropertyId belongs to. Write operations
 * are done for local properties using a provided PropertyInfoStore
 * implementations. Write operations on foreign properties are forbidden.
 *
 * @license GPL-2.0+
 */
class DispatchingPropertyInfoStore implements PropertyInfoStore {

	/**
	 * @var DispatchingPropertyInfoLookup
	 */
	private $lookup;

	/**
	 * @var PropertyInfoStore
	 */
	private $localStore;

	public function __construct( DispatchingPropertyInfoLookup $lookup, PropertyInfoStore $localStore ) {
		$this->lookup = $lookup;
		$this->localStore = $localStore;
	}

	/**
	 * @see PropertyInfoLookup::getAllPropertyInfo
	 *
	 * @return array[] An associative array mapping property IDs to info arrays.
	 * @throws StorageException
	 * @throws DBError
	 */
	public function getAllPropertyInfo() {
		return $this->lookup->getAllPropertyInfo();
	}

	/**
	 * @see PropertyInfoLookup::getPropertyInfo
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return array|null
	 * @throws StorageException
	 * @throws DBError
	 */
	public function getPropertyInfo( PropertyId $propertyId ) {
		return $this->lookup->getPropertyInfo( $propertyId );
	}

	/**
	 * @see PropertyInfoLookup::getPropertyInfoForDataType
	 *
	 * @param string $dataType
	 *
	 * @return array[] An associative array mapping property IDs to info arrays.
	 * @throws StorageException
	 * @throws DBError
	 */
	public function getPropertyInfoForDataType( $dataType ) {
		return $this->lookup->getPropertyInfoForDataType( $dataType );
	}

	/**
	 * @see PropertyInfoStore::setPropertyInfo
	 *
	 * @param PropertyId $propertyId
	 * @param array $info
	 *
	 * @throws StorageException
	 * @throws DBError
	 * @throws InvalidArgumentException when $propertyId belongs to the foreign repository
	 */
	public function setPropertyInfo( PropertyId $propertyId, array $info ) {
		if ( $propertyId->isForeign() ) {
			throw new InvalidArgumentException( 'Cannot set info to the foreign property id: ' . $propertyId->getSerialization() );
		}
		$this->localStore->setPropertyInfo( $propertyId, $info );
	}

	/**
	 * @see PropertyInfoStore::removePropertyInfo
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return bool true iff something was deleted
	 * @throws StorageException
	 * @throws DBError
	 * @throws InvalidArgumentException when $propertyId belongs to the foreign repository
	 */
	public function removePropertyInfo( PropertyId $propertyId ) {
		if ( $propertyId->isForeign() ) {
			throw new InvalidArgumentException( 'Cannot remove info from the foreign property id: ' . $propertyId->getSerialization() );
		}
		return $this->localStore->removePropertyInfo( $propertyId );
	}

}
