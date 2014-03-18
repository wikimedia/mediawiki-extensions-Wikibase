<?php

namespace Wikibase\DataModel\Serializers;

use Serializers\DispatchableSerializer;
use Serializers\Exceptions\SerializationException;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\DataModel\References;

/**
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class ReferencesSerializer implements DispatchableSerializer {

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
	 * @return boolean
	 */
	public function isSerializerFor( $object ) {
		return is_object( $object ) && $object instanceof References;
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
				'ReferencesSerializer can only serialize References objects'
			);
		}

		return $this->getSerialized( $object );
	}

	private function getSerialized( References $references ) {
		$serialization = array();

		foreach( $references as $reference ) {
			$serialization[] = $this->referenceSerializer->serialize( $reference );
		}

		return $serialization;
	}
}
