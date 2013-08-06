<?php

namespace Wikibase\Lib\Serializers;
use MWException;
use Wikibase\Reference;

/**
 * Serializer for Reference objects.
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
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ReferenceSerializer extends SerializerObject implements Unserializer {

	/**
	 * @see ApiSerializer::getSerialized
	 *
	 * @since 0.3
	 *
	 * @param mixed $reference
	 *
	 * @return array
	 * @throws MWException
	 */
	public function getSerialized( $reference ) {
		if ( !( $reference instanceof Reference ) ) {
			throw new MWException( 'ReferenceSerializer can only serialize Reference objects' );
		}

		$serialization = array();

		$serialization['hash'] = $reference->getHash();

		$snakSerializer = new SnakSerializer( $this->options );
		$snaksSerializer = new ByPropertyListSerializer( 'snaks', 'snak', $snakSerializer, $this->options );

		$serialization['snaklist'] = $snaksSerializer->getSerialized( $reference->getSnaks() );

		return $serialization;
	}

	/**
	 * @see Unserializer::newFromSerialization
	 *
	 * @since 0.4
	 *
	 * @param array $serialization
	 *
	 * @return Reference
	 * @throws MWException
	 */
	public function newFromSerialization( array $serialization ) {
		if ( !array_key_exists( 'snaklist', $serialization ) || !is_array( $serialization['snaklist'] ) ) {
			throw new MWException( 'A reference serialization needs to have a list of snaks' );
		}

		$snakUnserializer = new SnakSerializer( $this->options );
		$snaksUnserializer = new ByPropertyListUnserializer( $snakUnserializer );

		$snaks = $snaksUnserializer->newFromSerialization( $serialization['snaklist'] );

		$reference = new Reference( new \Wikibase\SnakList( $snaks ) );

		if ( array_key_exists( 'hash', $serialization ) && $serialization['hash'] !== $reference->getHash() ) {
			throw new MWException( 'If a hash is present in a reference serialization it needs to be correct' );
		}

		return $reference;
	}

}
