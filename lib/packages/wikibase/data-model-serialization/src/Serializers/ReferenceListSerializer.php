<?php

namespace Wikibase\DataModel\Serializers;

use Serializers\DispatchableSerializer;
use Serializers\Exceptions\SerializationException;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\DataModel\ReferenceList;

/**
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class ReferenceListSerializer implements DispatchableSerializer {

	/**
	 * @var Serializer
	 */
	protected $referenceSerializer;

	/**
	 * @param Serializer $referenceSerializer
	 */
	public function __construct( Serializer $referenceSerializer ) {
		$this->referenceSerializer = $referenceSerializer;
	}

	/**
	 * @see Serializer::isSerializerFor
	 *
	 * @param mixed $object
	 *
	 * @return bool
	 */
	public function isSerializerFor( $object ) {
		return $object instanceof ReferenceList;
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
		if ( !$this->isSerializerFor( $object ) ) {
			throw new UnsupportedObjectException(
				$object,
				'ReferenceListSerializer can only serialize ReferenceList objects'
			);
		}

		return $this->getSerialized( $object );
	}

	private function getSerialized( ReferenceList $references ) {
		$serialization = array();

		foreach( $references as $reference ) {
			$serialization[] = $this->referenceSerializer->serialize( $reference );
		}

		return $serialization;
	}

}
