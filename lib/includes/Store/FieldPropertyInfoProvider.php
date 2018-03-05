<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\PropertyId;
use Wikimedia\Assert\Assert;

/**
 * PropertyInfoProvider implementation based on a specific field in the array returned
 * by a PropertyInfoLookup.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class FieldPropertyInfoProvider implements PropertyInfoProvider {

	/**
	 * @var PropertyInfoLookup
	 */
	private $infoLookup;

	/**
	 * @var string The property info field name
	 */
	private $propertyInfoKey;

	/**
	 * @param PropertyInfoLookup $infoLookup
	 * @param string $propertyInfoKey Name of the desired field in the PropertyInfo array.
	 *        Use one of the PropertyInfoStore::KEY_XXX constants.
	 */
	public function __construct( PropertyInfoLookup $infoLookup, $propertyInfoKey ) {
		Assert::parameterType( 'string', $propertyInfoKey, '$propertyInfoKey' );

		$this->infoLookup = $infoLookup;
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
		$info = $this->infoLookup->getPropertyInfo( $propertyId );

		if ( $info !== null && isset( $info[$this->propertyInfoKey] ) ) {
			return $info[$this->propertyInfoKey];
		} else {
			return null;
		}
	}

}
