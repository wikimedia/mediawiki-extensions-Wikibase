<?php

namespace Wikibase;

use MWException;
use OutOfBoundsException;
use RuntimeException;

/**
 * Helper for doing indexed lookups of objects by property id.
 *
 * This is a light weight alternative approach to using something
 * like GenericArrayObject with the advantages that no extra interface
 * is needed and that indexing does not happen automatically.
 *
 * Lack of automatic indexing means that you will need to call the
 * buildIndex method before doing any lookups.
 *
 * Since no extra interface is used, the user is responsible for only
 * adding objects that have a getPropertyId method that returns either
 * a string or integer when called with no arguments.
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
 * @since 0.2
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ByPropertyIdArray extends \ArrayObject {

	/**
	 * @since 0.2
	 *
	 * @var null|object[]
	 */
	protected $byId = null;

	/**
	 * Builds the index for doing lookups by property id.
	 *
	 * @since 0.2
	 */
	public function buildIndex() {
		$this->byId = array();

		foreach ( $this as $object ) {
			$propertyId = $object->getPropertyId()->getNumericId();

			if ( !array_key_exists( $propertyId, $this->byId ) ) {
				$this->byId[$propertyId] = array();
			}

			$this->byId[$propertyId][] = $object;
		}
	}

	/**
	 * Returns the property ids in the index as integers.
	 *
	 * @since 0.2
	 *
	 * @return integer[]
	 * @throws RuntimeException
	 */
	public function getPropertyIds() {
		if ( $this->byId === null ) {
			throw new RuntimeException( 'Index not build, call buildIndex first' );
		}

		return array_keys( $this->byId );
	}

	/**
	 * Returns the objects under the provided property id in the index.
	 *
	 * @since 0.2
	 *
	 * @param integer $propertyId
	 *
	 * @return object[]
	 * @throws RuntimeException|OutOfBoundsException
	 */
	public function getByPropertyId( $propertyId ) {
		if ( $this->byId === null ) {
			throw new RuntimeException( 'Index not build, call buildIndex first' );
		}

		if ( !( array_key_exists( $propertyId, $this->byId ) ) ) {
			throw new OutOfBoundsException( 'Property id array key does not exist.' );
		}

		return $this->byId[$propertyId];
	}

}
