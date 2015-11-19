<?php

namespace Wikibase\Lib;

use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\PropertyInfoStore;
use Wikimedia\Assert\Assert;

/**
 * SnakUrlExpander that uses an PropertyInfoStore to find
 * a property's data type ID.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class PropertyInfoSnakUrlExpander implements SnakUrlExpander {

	/**
	 * @var PropertyInfoStore
	 */
	private $infoStore;

	/**
	 * @var string
	 */
	private $propertyInfoKey;

	/**
	 * @param PropertyInfoStore $infoStore
	 * @param string $propertyInfoKey
	 */
	public function __construct( PropertyInfoStore $infoStore, $propertyInfoKey ) {
		Assert::parameterType( 'string', $propertyInfoKey, '$propertyInfoKey' );

		$this->infoStore = $infoStore;
		$this->propertyInfoKey = $propertyInfoKey;
	}

	/**
	 * @see SnakUrlExpander::expandUrl
	 *
	 * @param PropertyValueSnak $snak
	 *
	 * @return string|null A URL or URI derived from the Snak, or null if no such URL
	 *         could be determined.
	 */
	public function expandUrl( PropertyValueSnak $snak ) {
		$propertyId = $snak->getPropertyId();
		$value = $snak->getDataValue();

		Assert::parameterType( 'DataValues\StringValue', $value, '$snak->getValue()' );

		$info = $this->infoStore->getPropertyInfo( $propertyId );

		if ( $info === null || !isset( $info[$this->propertyInfoKey] ) ) {
			return null;
		}

		$pattern = $info[$this->propertyInfoKey];
		$id = urlencode( $value->getValue() );

		$url = str_replace( '$1', $id, $pattern );
		return $url;
	}

}
