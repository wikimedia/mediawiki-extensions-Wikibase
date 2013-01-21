<?php

namespace Wikibase\Lib\Serializers;
use MWException;

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

	public function __construct(  ) {

	}

	/**
	 * @param mixed $object
	 *
	 * @return Serializer
	 * @throws MWException
	 */
	public function newSerializerForObject( $object ) {
		if ( !is_object( $object ) ) {
			throw new MWException( 'newSerializerForObject only accepts objects and got ' . gettype( $object ) );
		}

		switch ( true ) {
			case ( $object instanceof \Wikibase\Snak ):
				return new SnakSerializer();
				break;
			case ( $object instanceof \Wikibase\Reference ):
				return new ReferenceSerializer();
				break;
			case ( $object instanceof \Wikibase\Item ):
				return new ItemSerializer();
				break;
			case ( $object instanceof \Wikibase\Property ):
				return new PropertySerializer();
				break;
			case ( $object instanceof \Wikibase\Entity ):
				return new EntitySerializer();
				break;
			case ( $object instanceof \Wikibase\Claim ):
				return new ClaimSerializer();
				break;
			case ( $object instanceof \Wikibase\Claims ):
				return new ClaimsSerializer();
				break;
		}

		throw new MWException( 'There is no serializer for the provided type of object "' . get_class( $object ) . '"' );
	}

	/**
	 * @param string $className
	 *
	 * @return Unserializer
	 * @throws MWException
	 */
	public function newUnserializerForClass( $className ) {
		if ( !is_string( $className ) ) {
			throw new MWException( '$className needs to be a string' );
		}

		switch ( ltrim( $className, '\\' ) ) {
			case 'Wikibase\Snak':
				return new SnakSerializer();
				break;
			case 'Wikibase\Claim':
				return new ClaimSerializer();
				break;
		}

		throw new MWException( '"' . $className . '" has no associated unserializer' );
	}

}