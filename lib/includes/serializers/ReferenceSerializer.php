<?php

namespace Wikibase\Lib\Serializers;

use InvalidArgumentException;
use OutOfBoundsException;
use Wikibase\Reference;
use Wikibase\SnakList;

/**
 * Serializer for Reference objects.
 *
 * See docs/json.wiki for details of the format.
 *
 * @since 0.3
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
	 * @throws InvalidArgumentException
	 */
	public function getSerialized( $reference ) {
		if ( !( $reference instanceof Reference ) ) {
			throw new InvalidArgumentException( 'ReferenceSerializer can only serialize Reference objects' );
		}

		//NOTE: when changing the serialization structure, update docs/json.wiki too!

		$serialization = array();

		$serialization['hash'] = $reference->getHash();

		$snakSerializer = new SnakSerializer( $this->options );
		$snaksSerializer = new ByPropertyListSerializer( 'snak', $snakSerializer, $this->options );

		$serialization['snaks'] = $snaksSerializer->getSerialized( $reference->getSnaks() );

		$serialization['snaks-order'] = array();
		foreach( $reference->getSnaks() as $snak ) {
			$id = $snak->getPropertyId()->getPrefixedId();
			if( !in_array( $id, $serialization['snaks-order'] ) ) {
				$serialization['snaks-order'][] = $id;
			}
		}
		$this->setIndexedTagName( $serialization['snaks-order'], 'property' );

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
	 * @throws InvalidArgumentException
	 * @throws OutOfBoundsException
	 */
	public function newFromSerialization( array $serialization ) {
		if ( !array_key_exists( 'snaks', $serialization ) || !is_array( $serialization['snaks'] ) ) {
			throw new InvalidArgumentException( 'A reference serialization needs to have a list of snaks' );
		}

		$sortedSnaks = array();

		if(
			!array_key_exists( 'snaks-order', $serialization )
			|| !is_array( $serialization['snaks-order'] )
		) {
			$sortedSnaks = $serialization['snaks'];

		} else {
			foreach( $serialization['snaks-order'] as $propertyId ) {
				if( !isset( $serialization['snaks'][$propertyId] ) ) {
					throw new OutOfBoundsException( 'No snaks with property id "' . $propertyId . '" found '
					. 'in "snaks" parameter although specified in "snaks-order"' );
				}

				$sortedSnaks[$propertyId] = $serialization['snaks'][$propertyId];
			}

			$missingProperties = array_diff_key( $sortedSnaks, $serialization['snaks'] );

			if( count( $missingProperties ) > 0 ) {
				throw new OutOfBoundsException( 'Property ids ' . implode( ', ', $missingProperties )
				. ' have not been specified in "snaks-order"' );
			}
		}

		$snakUnserializer = new SnakSerializer( $this->options );
		$snaksUnserializer = new ByPropertyListUnserializer( $snakUnserializer );

		$snaks = $snaksUnserializer->newFromSerialization( $sortedSnaks );

		$reference = new Reference( new SnakList( $snaks ) );

		if ( array_key_exists( 'hash', $serialization ) && $serialization['hash'] !== $reference->getHash() ) {
			throw new InvalidArgumentException( 'If a hash is present in a reference serialization it needs to be correct' );
		}

		return $reference;
	}

}
