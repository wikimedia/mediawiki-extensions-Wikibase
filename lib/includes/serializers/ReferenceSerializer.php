<?php

namespace Wikibase\Lib\Serializers;
use MWException;
use Wikibase\Reference;

/**
 * Serializer for Reference objects.
 *
 * See docs/json.wiki for details of the format.
 *
 * @since 0.3
 *
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

		//NOTE: when changing the serialization structure, update docs/json.wiki too!

		$serialization = array();

		$serialization['hash'] = $reference->getHash();

		$snakSerializer = new SnakSerializer( $this->options );
		$snaksSerializer = new ByPropertyListSerializer( 'snak', $snakSerializer, $this->options );

		$serialization['snaks'] = $snaksSerializer->getSerialized( $reference->getSnaks() );

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
		if ( !array_key_exists( 'snaks', $serialization ) || !is_array( $serialization['snaks'] ) ) {
			throw new MWException( 'A reference serialization needs to have a list of snaks' );
		}

		$snakUnserializer = new SnakSerializer( $this->options );
		$snaksUnserializer = new ByPropertyListUnserializer( $snakUnserializer );

		$snaks = $snaksUnserializer->newFromSerialization( $serialization['snaks'] );

		$reference = new Reference( new \Wikibase\SnakList( $snaks ) );

		if ( array_key_exists( 'hash', $serialization ) && $serialization['hash'] !== $reference->getHash() ) {
			throw new MWException( 'If a hash is present in a reference serialization it needs to be correct' );
		}

		return $reference;
	}

}
