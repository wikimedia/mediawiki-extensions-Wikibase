<?php

namespace Wikibase;

use DataValues\StringValue;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\Lib\PropertyNotFoundException;

/**
 * Finds URLs given a list of snaks.
 *
 * If a snaks property is not found or the type of DataValue
 * does not match the expected one for URLs, the snak is ignored
 * silently.
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ReferencedUrlFinder {

	/**
	 * @since 0.4
	 *
	 * @var PropertyDataTypeLookup
	 */
	protected $propertyDataTypeLookup;

	/**
	 * @var string[]
	 */
	protected $foundURLs;

	/**
	 * @since 0.4
	 *
	 * @param PropertyDataTypeLookup $propertyDataTypeLookup
	 */
	public function __construct( PropertyDataTypeLookup $propertyDataTypeLookup ) {
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
	}

	/**
	 * @param Snak[] $snaks
	 *
	 * @return string[]
	 */
	public function findSnakLinks( array $snaks ) {
		$this->foundURLs = array();

		foreach ( $snaks as $snak ) {
			if( $snak instanceof PropertyValueSnak ) {
				if ( $this->isUrlProperty( $snak->getPropertyId() ) ) {
					$this->findPropertyValueSnakLinks( $snak );
				}
			}
		}

		return array_unique( $this->foundURLs );
	}

	protected function findPropertyValueSnakLinks( PropertyValueSnak $snak ) {
		$snakValue = $snak->getDataValue();

		if ( $snakValue instanceof StringValue ) {
			$this->foundURLs[] = $snakValue->getValue();
		}
	}

	protected function isUrlProperty( EntityId $propertyId ) {
		try {
			$type = $this->propertyDataTypeLookup->getDataTypeIdForProperty( $propertyId );
		} catch ( PropertyNotFoundException $ex ) {
			return false;
		}

		return $type === 'url';
	}

}


