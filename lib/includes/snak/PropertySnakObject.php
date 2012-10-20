<?php

namespace Wikibase;

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
	 * @var integer
	 */
	protected $propertyId;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param integer $propertyId
	 */
	public function __construct( $propertyId ) {
		$this->propertyId = $propertyId;
	}

	/**
	 * @see PropertySnak::getPropertyId
	 *
	 * @since 0.1
	 *
	 * @return integer
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
		return serialize( $this->propertyId );
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
		$this->propertyId = unserialize( $serialized );
	}

}