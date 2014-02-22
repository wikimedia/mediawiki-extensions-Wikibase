<?php

namespace Wikibase\DataModel\Serializers;

use Serializers\Exceptions\SerializationException;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\DataModel\Snak\Snaks;

/**
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class SnaksSerializer implements Serializer {

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
	 * @see Serializer::isSerializerFor
	 *
	 * @param mixed $object
	 *
	 * @return boolean
	 */
	public function isSerializerFor( $object ) {
		return is_object( $object ) && $object instanceof Snaks;
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
				'SnaksSerializer can only serialize Snaks objects'
			);
		}

		return $this->getSerialized( $object );
	}

	private function getSerialized( Snaks $snaks ) {
		$serialization = array();

		foreach( $snaks as $snak ) {
			$serialization[$snak->getPropertyId()->getPrefixedId()][] = $this->snakSerializer->serialize( $snak );
		}

		return $serialization;
	}
}
