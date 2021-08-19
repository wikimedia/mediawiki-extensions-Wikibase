<?php

namespace Wikibase\DataModel\Serializers;

use Serializers\Exceptions\SerializationException;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\DataModel\Snak\TypedSnak;

/**
 * Package private
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TypedSnakSerializer implements Serializer {

	/**
	 * @var Serializer
	 */
	private $snakSerializer;

	public function __construct( Serializer $snakSerializer ) {
		$this->snakSerializer = $snakSerializer;
	}

	/**
	 * @see Serializer::serialize
	 *
	 * @param TypedSnak $object
	 *
	 * @throws SerializationException
	 * @return array
	 */
	public function serialize( $object ) {
		$this->assertIsSerializerFor( $object );

		return $this->getSerialized( $object );
	}

	private function assertIsSerializerFor( $object ) {
		if ( !( $object instanceof TypedSnak ) ) {
			throw new UnsupportedObjectException(
				$object,
				'TypedSnakSerializer can only serialize TypedSnak objects'
			);
		}
	}

	private function getSerialized( TypedSnak $typedSnak ) {
		$serialization = $this->snakSerializer->serialize( $typedSnak->getSnak() );

		$serialization['datatype'] = $typedSnak->getDataTypeId();

		return $serialization;
	}

}
