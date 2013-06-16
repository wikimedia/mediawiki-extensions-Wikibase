<?php

namespace Wikibase;

use DataValues\IllegalValueException;
use InvalidArgumentException;
use MWException;

/**
 * Factory for creating new snaks.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.3
 *
 * @ingroup WikibaseDataModel
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
	 * @param EntityId    $propertyId
	 * @param string      $snakType
	 * @param string|null $valueType
	 * @param mixed|null  $rawValue
	 *
	 * @return Snak
	 *
	 * @throws IllegalValueException
	 * @throws InvalidArgumentException
	 */
	public function newSnak( EntityId $propertyId, $snakType, $valueType = null, $rawValue = null ) {
		//TODO: needs test

		if ( $propertyId->getEntityType() !== Property::ENTITY_TYPE ) {
			throw new InvalidArgumentException( 'Expected an EntityId of a property' );
		}

		switch ( $snakType ) {
			case 'value':
				//TODO: inject DataValueFactory
				$dataValue = \DataValues\DataValueFactory::singleton()->newDataValue( $valueType, $rawValue );

				$snak = new PropertyValueSnak( $propertyId, $dataValue );
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

		assert( isset( $snak ) );

		return $snak;
	}

}