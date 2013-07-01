<?php

namespace Wikibase\Lib;

use InvalidArgumentException;
use Wikibase\EntityId;
use Wikibase\EntityLookup;
use Wikibase\Property;

/**
 * PropertyDataTypeLookup that uses an EntityLookup to find
 * a property's data type ID.
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
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityRetrievingDataTypeLookup implements PropertyDataTypeLookup {

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	public function __construct( EntityLookup $entityLookup ) {
		$this->entityLookup = $entityLookup;
	}

	/**
	 * @since 0.4
	 *
	 * @param EntityId $propertyId
	 *
	 * @return string
	 */
	public function getDataTypeIdForProperty( EntityId $propertyId ) {
		$this->verifyIdIsOfAProperty( $propertyId );
		return $this->getProperty( $propertyId )->getDataTypeId();
	}

	private function verifyIdIsOfAProperty( EntityId $propertyId ) {
		if ( $propertyId->getEntityType() !== Property::ENTITY_TYPE ) {
			throw new InvalidArgumentException( '$propertyId with non-property entity type provided' );
		}
	}

	/**
	 * @param EntityId $propertyId
	 *
	 * @return Property
	 * @throws PropertyNotFoundException
	 */
	private function getProperty( EntityId $propertyId ) {
		$property = $this->entityLookup->getEntity( $propertyId );

		if ( $property === null ) {
			throw new PropertyNotFoundException( $propertyId );
		}

		assert( $property instanceof Property );
		return $property;
	}

}
