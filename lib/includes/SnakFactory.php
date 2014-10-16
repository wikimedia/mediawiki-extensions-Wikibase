<?php

namespace Wikibase;

use DataValues\DataValue;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;

/**
 * Factory for creating new snaks.
 *
 * @deprecated
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class SnakFactory {

	/**
	 * Builds and returns a new snak from the provided property, snak type
	 * and optional snak value and value type.
	 *
	 * @since 0.4
	 *
	 * @param PropertyId $propertyId
	 * @param string $snakType
	 * @param DataValue $value
	 *
	 * @return Snak
	 *
	 * @throws InvalidArgumentException
	 */
	public function newSnak( PropertyId $propertyId, $snakType, DataValue $value = null ) {
		switch ( $snakType ) {
			case 'value':
				if ( $value === null ) {
					throw new InvalidArgumentException( "`value` snaks require a the $value parameter to be set!" );
				}

				$snak = new PropertyValueSnak( $propertyId, $value );
				break;
			case 'novalue':
				$snak = new PropertyNoValueSnak( $propertyId );
				break;
			case 'somevalue':
				$snak = new PropertySomeValueSnak( $propertyId );
				break;
			default:
				throw new InvalidArgumentException( "bad snak type: $snakType" );
		}

		return $snak;
	}

}
