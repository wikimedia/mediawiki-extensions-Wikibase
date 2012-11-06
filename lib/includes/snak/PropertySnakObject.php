<?php

namespace Wikibase;
use MWException;

/**
 * Base class for property snaks.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#Snaks
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
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class PropertySnakObject extends SnakObject implements PropertySnak {

	/**
	 * @since 0.1
	 *
	 * @var EntityId
	 */
	protected $propertyId;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param EntityId $propertyId
	 *
	 * @throws MWException
	 */
	public function __construct( $propertyId ) {
		// The first two checks here are for compat with passing an integer.
		// The integer passing is deprecated.
		// TODO: update
		if ( is_int( $propertyId ) ) {
			$propertyId = new EntityId( Property::ENTITY_TYPE, $propertyId );
		}

		if ( !$propertyId instanceof EntityId ) {
			throw new MWException( '$propertyId should be a EntityId' );
		}

		if ( $propertyId->getEntityType() !== Property::ENTITY_TYPE ) {
			throw new MWException( 'The $propertyId of a property snak can only be an ID of a Property object' );
		}

		$this->propertyId = $propertyId;
	}

	/**
	 * @see PropertySnak::getPropertyId
	 *
	 * @since 0.1
	 *
	 * @return EntityId
	 */
	public function getPropertyId() {
		return $this->propertyId;
	}

	/**
	 * @see Serializable::serialize
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function serialize() {
		return serialize( $this->propertyId->getNumericId() );
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @since 0.1
	 *
	 * @param string $serialized
	 *
	 * @return PropertySnak
	 */
	public function unserialize( $serialized ) {
		$this->propertyId = new EntityId( Property::ENTITY_TYPE, (int)unserialize( $serialized ) );
	}

}
