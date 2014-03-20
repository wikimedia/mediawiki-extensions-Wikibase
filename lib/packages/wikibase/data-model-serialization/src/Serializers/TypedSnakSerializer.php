<?php

namespace Wikibase\DataModel\Serializers;

use Serializers\Exceptions\SerializationException;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\DataModel\Snak\TypedSnak;

/**
 * @since 1.0
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TypedSnakSerializer implements Serializer {

	/**
	 * @var Serializer
	 */
	protected $snakSerializer;

	/**
	 * @param Serializer $snakSerializer
	 */
	public function __construct( Serializer $snakSerializer ) {
		$this->snakSerializer = $snakSerializer;
	}

	/**
	 * @see Serializer::serialize
	 *
	 * @param mixed $object
	 *
	 * @return array
	 * @throws SerializationException
	 */
	public function serialize( $object ) {
		$this->assertIsSerializerFor( $object );

		return $this->getSerialized( $object );
	}

	private function assertIsSerializerFor( $object ) {
		if ( !is_object( $object ) || !( $object instanceof TypedSnak ) ) {
			throw new UnsupportedObjectException(
				$object,
				'SnakSerializer can only serialize Snak objects'
			);
		}
	}

	private function getSerialized( TypedSnak $typedSnak ) {
		$serialization = $this->snakSerializer->serialize( $typedSnak->getSnak() );

		$serialization['datatype'] = $typedSnak->getDataTypeId();

		return $serialization;
	}
}
