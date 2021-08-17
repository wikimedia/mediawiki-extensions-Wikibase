<?php

namespace Wikibase\DataModel\Serializers;

use Serializers\DispatchableSerializer;
use Serializers\Exceptions\SerializationException;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;

/**
 * Package private
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 * @author Addshore
 */
class SnakSerializer implements DispatchableSerializer {

	/**
	 * @var Serializer
	 */
	private $dataValueSerializer;

	/**
	 * @var bool
	 */
	private $serializeWithHash;

	/**
	 * @param Serializer $dataValueSerializer
	 * @param bool $serializeWithHash
	 */
	public function __construct( Serializer $dataValueSerializer, $serializeWithHash = true ) {
		$this->dataValueSerializer = $dataValueSerializer;
		$this->serializeWithHash = $serializeWithHash;
	}

	/**
	 * @see Serializer::isSerializerFor
	 *
	 * @param mixed $object
	 *
	 * @return bool
	 */
	public function isSerializerFor( $object ) {
		return $object instanceof Snak;
	}

	/**
	 * @see Serializer::serialize
	 *
	 * @param Snak $object
	 *
	 * @throws SerializationException
	 * @return array
	 */
	public function serialize( $object ) {
		if ( !$this->isSerializerFor( $object ) ) {
			throw new UnsupportedObjectException(
				$object,
				'SnakSerializer can only serialize Snak objects'
			);
		}

		return $this->getSerialized( $object );
	}

	private function getSerialized( Snak $snak ) {
		$serialization = [
			'snaktype' => $snak->getType(),
			'property' => $snak->getPropertyId()->getSerialization(),
		];

		if ( $this->serializeWithHash ) {
			$serialization['hash'] = $snak->getHash();
		}

		if ( $snak instanceof PropertyValueSnak ) {
			$serialization['datavalue'] = $this->dataValueSerializer->serialize( $snak->getDataValue() );
		}

		return $serialization;
	}

}
