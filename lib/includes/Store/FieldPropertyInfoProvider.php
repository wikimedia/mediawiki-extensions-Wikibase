<?php

namespace Wikibase\Lib;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Edrsf\PropertyInfoLookup;
use Wikimedia\Assert\Assert;

/**
 * PropertyInfoProvider implementation based on a specific field in the array returned
 * by a PropertyInfoLookup.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class FieldPropertyInfoProvider implements PropertyInfoProvider {

	/**
	 * @var \Wikibase\Edrsf\PropertyInfoLookup
	 */
	private $infoLookup;

	/**
	 * @var string The property info field name
	 */
	private $propertyInfoKey;

	/**
	 * @param \Wikibase\Edrsf\PropertyInfoLookup $infoLookup
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
	 * @throws \Wikibase\Edrsf\StorageException
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
