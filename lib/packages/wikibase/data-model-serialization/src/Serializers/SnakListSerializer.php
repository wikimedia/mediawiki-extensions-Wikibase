<?php

namespace Wikibase\DataModel\Serializers;

use Serializers\DispatchableSerializer;
use Serializers\Exceptions\SerializationException;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * Package private
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class SnakListSerializer extends MapSerializer implements DispatchableSerializer {

	/**
	 * @var Serializer
	 */
	private $snakSerializer;

	/**
	 * @param Serializer $snakSerializer
	 */
	public function __construct( Serializer $snakSerializer, bool $useObjectsForEmptyMaps ) {
		parent::__construct( $useObjectsForEmptyMaps );
		$this->snakSerializer = $snakSerializer;
	}

	/**
	 * @see Serializer::isSerializerFor
	 *
	 * @param mixed $object
	 *
	 * @return bool
	 */
	public function isSerializerFor( $object ) {
		return $object instanceof SnakList;
	}

	/**
	 * @see Serializer::serialize
	 *
	 * @param SnakList $object
	 *
	 * @throws SerializationException
	 * @return array[]
	 */
	public function serialize( $object ) {
		if ( !$this->isSerializerFor( $object ) ) {
			throw new UnsupportedObjectException(
				$object,
				'SnakListSerializer can only serialize SnakList objects'
			);
		}
		return $this->serializeMap( $this->generateSerializedArrayRepresentation( $object ) );
	}

	protected function generateSerializedArrayRepresentation( SnakList $snaks ): array {
		$serialization = [];

		/**
		 * @var Snak $snak
		 */
		foreach ( $snaks as $snak ) {
			$propertyId = $snak->getPropertyId()->getSerialization();
			$serialization[$propertyId][] = $this->snakSerializer->serialize( $snak );
		}

		return $serialization;
	}
}
