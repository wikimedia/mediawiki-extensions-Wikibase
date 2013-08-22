<?php

namespace Wikibase;

use DataValues\StringValue;
use OutOfBoundsException;
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
 */
class ReferencedUrlFinder {

	/**
	 * @since 0.4
	 *
	 * @var PropertyDataTypeLookup
	 */
	protected $propertyDataTypeLookup;

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
		$foundURLs = array();

		foreach ( $snaks as $snak ) {
			// PropertyValueSnaks might have a value referencing a URL, find those:
			if( $snak instanceof PropertyValueSnak ) {
				try {
					$type = $this->propertyDataTypeLookup->getDataTypeIdForProperty( $snak->getPropertyId() );
				} catch ( OutOfBoundsException $ex ) {
					wfLogWarning( 'No data type known for property ' . $snak->getPropertyId() );
					continue;
				} catch ( PropertyNotFoundException $ex ) {
					wfLogWarning( 'No data type known for unknown property ' . $snak->getPropertyId() );
					continue;
				}
				
				if ( $type !== 'url' ) {
					continue;
				}

				$snakValue = $snak->getDataValue();

				if( $snakValue === null ) {
					// shouldn't ever run into this, but make sure!
					continue;
				}

				if ( $snakValue instanceof StringValue ) {
					$foundURLs[] = $snakValue->getValue();
				} else {
					wfLogWarning( 'Unexpected value type for url: ' . $snakValue->getType() );
				}
			}
		}

		return array_unique( $foundURLs );
	}

}


