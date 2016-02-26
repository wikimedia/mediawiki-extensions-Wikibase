<?php

namespace Wikibase\Lib;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\StorageException;
use Wikibase\PropertyInfoStore;
use Wikimedia\Assert\Assert;

/**
 * PropertyInfoProvider implementation based on a specific field in the array returned
 * by a PropertyInfoStore.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class FieldPropertyInfoProvider implements PropertyInfoProvider {

	/**
	 * @var PropertyInfoStore
	 */
	private $infoStore;

	/**
	 * @var string The property info field name
	 */
	private $propertyInfoKey;

	/**
	 * @param PropertyInfoStore $infoStore
	 * @param string $propertyInfoKey Name of the desired field in the PropertyInfo array.
	 *        Use one of the PropertyInfoStore::KEY_XXX constants.
	 */
	public function __construct( PropertyInfoStore $infoStore, $propertyInfoKey ) {
		Assert::parameterType( 'string', $propertyInfoKey, '$propertyInfoKey' );

		$this->infoStore = $infoStore;
		$this->propertyInfoKey = $propertyInfoKey;
	}

	/**
	 * Returns the value for the property info field specified in the constructor.
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return mixed|null
	 *
	 * @throws StorageException
	 */
	public function getPropertyInfo( PropertyId $propertyId ) {
		$info = $this->infoStore->getPropertyInfo( $propertyId );

		if ( $info !== null && isset( $info[$this->propertyInfoKey] ) ) {
			return $info[$this->propertyInfoKey];
		} else {
			return null;
		}
	}

}
