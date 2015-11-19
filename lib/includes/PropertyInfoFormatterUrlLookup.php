<?php

namespace Wikibase\Lib;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyFormatterUrlLookup;
use Wikibase\PropertyInfoStore;

/**
 * PropertyFormatterUrlLookup that uses an PropertyInfoStore to find
 * a property's data type ID.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class PropertyInfoFormatterUrlLookup implements PropertyFormatterUrlLookup {

	/**
	 * @var PropertyInfoStore
	 */
	private $infoStore;

	/**
	 * @param PropertyInfoStore $infoStore
	 */
	public function __construct( PropertyInfoStore $infoStore ) {
		$this->infoStore = $infoStore;
	}

	/**
	 * @see PropertyFormatterUrlLookup::getUrlPatternForProperty
	 * @since 0.5
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return string|null
	 */
	public function getUrlPatternForProperty( PropertyId $propertyId ) {
		$info = $this->infoStore->getPropertyInfo( $propertyId );

		if ( $info !== null && isset( $info[PropertyInfoStore::KEY_FORMATTER_URL] ) ) {
			return $info[PropertyInfoStore::KEY_FORMATTER_URL];
		}

		return null;
	}

}
