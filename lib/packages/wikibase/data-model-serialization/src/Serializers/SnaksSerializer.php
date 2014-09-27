<?php

namespace Wikibase\DataModel\Serializers;

use Serializers\DispatchableSerializer;
use Serializers\Exceptions\SerializationException;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\Snaks;

/**
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class SnaksSerializer implements DispatchableSerializer {

	/**
	 * @var Serializer
	 */
	protected $snakSerializer;

	/**
	 * @var bool
	 */
	protected $useObjectsForMaps;

	/**
	 * @param Serializer $snakSerializer
	 * @param bool $useObjectsForMaps
	 */
	public function __construct( Serializer $snakSerializer, $useObjectsForMaps ) {
		$this->snakSerializer = $snakSerializer;
		$this->useObjectsForMaps = $useObjectsForMaps;
	}

	/**
	 * @see Serializer::isSerializerFor
	 *
	 * @param mixed $object
	 *
	 * @return bool
	 */
	public function isSerializerFor( $object ) {
		return $object instanceof Snaks;
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

		/**
		 * @var Snak $snak
		 */
		foreach( $snaks as $snak ) {
			$serialization[$snak->getPropertyId()->getPrefixedId()][] = $this->snakSerializer->serialize( $snak );
		}

		if ( $this->useObjectsForMaps ) {
			$serialization = (object)$serialization;
		}
		return $serialization;
	}

}
