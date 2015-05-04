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
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class SnakSerializer implements DispatchableSerializer {

	/**
	 * @var Serializer
	 */
	protected $dataValueSerializer;

	/**
	 * @param Serializer $dataValueSerializer
	 */
	public function __construct( Serializer $dataValueSerializer ) {
		$this->dataValueSerializer = $dataValueSerializer;
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
	 * @return array
	 * @throws SerializationException
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
		$serialization = array(
			'snaktype' => $snak->getType(),
			'property' => $snak->getPropertyId()->getSerialization(),
			'hash' => $snak->getHash()
		);

		if ( $snak instanceof PropertyValueSnak ) {
			$serialization['datavalue'] = $this->dataValueSerializer->serialize( $snak->getDataValue() );
		}

		return $serialization;
	}

}
