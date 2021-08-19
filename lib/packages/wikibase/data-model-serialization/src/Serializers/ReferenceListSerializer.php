<?php

namespace Wikibase\DataModel\Serializers;

use Serializers\DispatchableSerializer;
use Serializers\Exceptions\SerializationException;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\DataModel\ReferenceList;

/**
 * Package private
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class ReferenceListSerializer implements DispatchableSerializer {

	/**
	 * @var Serializer
	 */
	private $referenceSerializer;

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
	 * @param ReferenceList $object
	 *
	 * @throws SerializationException
	 * @return array[]
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
		$serialization = [];

		foreach ( $references as $reference ) {
			$serialization[] = $this->referenceSerializer->serialize( $reference );
		}

		return $serialization;
	}

}
