<?php

namespace Wikibase\Lib\Serializers;

use InvalidArgumentException;
use OutOfBoundsException;

/**
 * Factory for constructing Serializer and Unserializer objects.
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
class SerializerFactory {

	/**
	 * @param mixed $object
	 * @param SerializationOptions $options
	 *
	 * @return Serializer
	 * @throws OutOfBoundsException
	 * @throws InvalidArgumentException
	 */
	public function newSerializerForObject( $object, $options = null ) {
		if ( !is_object( $object ) ) {
			throw new InvalidArgumentException( 'newSerializerForObject only accepts objects and got ' . gettype( $object ) );
		}

		switch ( true ) {
			case ( $object instanceof \Wikibase\Snak ):
				return new SnakSerializer( $options );
			case ( $object instanceof \Wikibase\Reference ):
				return new ReferenceSerializer( $options );
			case ( $object instanceof \Wikibase\Item ):
				return new ItemSerializer( $options );
			case ( $object instanceof \Wikibase\Property ):
				return new PropertySerializer( $options );
			case ( $object instanceof \Wikibase\Entity ):
				return new EntitySerializer( $options );
			case ( $object instanceof \Wikibase\Claim ):
				return new ClaimSerializer( $options );
			case ( $object instanceof \Wikibase\Claims ):
				return new ClaimsSerializer( $options );
		}

		throw new OutOfBoundsException( 'There is no serializer for the provided type of object "' . get_class( $object ) . '"' );
	}

	/**
	 * @param string $className
	 * @param SerializationOptions $options
	 *
	 * @return Unserializer
	 * @throws OutOfBoundsException
	 * @throws InvalidArgumentException
	 */
	public function newUnserializerForClass( $className, $options = null ) {
		if ( !is_string( $className ) ) {
			throw new OutOfBoundsException( '$className needs to be a string' );
		}

		switch ( ltrim( $className, '\\' ) ) {
			case 'Wikibase\Snak':
				return new SnakSerializer( $options );
			case 'Wikibase\Reference':
				return new ReferenceSerializer( $options );
			case 'Wikibase\Claim':
				return new ClaimSerializer( $options );
		}

		throw new OutOfBoundsException( '"' . $className . '" has no associated unserializer' );
	}

}