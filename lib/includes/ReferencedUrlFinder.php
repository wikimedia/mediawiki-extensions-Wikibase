<?php

namespace Wikibase;

use DataValues\StringValue;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\Lib\PropertyNotFoundException;

/**
 * Finds URLs given a list of entities or a list of claims.
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
		} else {
			wfLogWarning( 'Unexpected value type for url: ' . $snakValue->getType() );
		}
	}

	protected function isUrlProperty( EntityId $propertyId ) {
		try {
			$type = $this->propertyDataTypeLookup->getDataTypeIdForProperty( $propertyId );
		} catch ( PropertyNotFoundException $ex ) {
			// FIXME: wrong place to stop exception propagation.
			// Either do not catch this here or throw a new exception instead.
			wfLogWarning( 'No data type known for unknown property ' . $propertyId );
			return false;
		}

		return $type === 'url';
	}

}


