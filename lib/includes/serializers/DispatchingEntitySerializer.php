<?php

namespace Wikibase\Lib\Serializers;

use InvalidArgumentException;
use Wikibase\Entity;

/**
 * Serializer for entities, dispatching to the appropriate serializer for each entity type.
 *
 * See docs/json.wiki for details of the format.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class DispatchingEntitySerializer extends SerializerObject implements Unserializer {

	/**
	 * @var SerializerFactory
	 */
	protected $serializerFactory;

	/**
	 * @var Serializer[]
	 */
	protected $serializers;

	/**
	 * @var Unserializer[]
	 */
	protected $unserializers;

	/**
	 * @param SerializerFactory $serializerFactory
	 * @param SerializationOptions $options
	 */
	public function __construct( SerializerFactory $serializerFactory, SerializationOptions $options = null ) {
		parent::__construct( $options );

		$this->serializerFactory = $serializerFactory;
	}

	/**
	 * @param Entity $entity
	 *
	 * @return array
	 * @throws InvalidArgumentException
	 */
	public function getSerialized( $entity ) {
		if ( !( $entity instanceof Entity ) ) {
			throw new InvalidArgumentException( 'EntitySerializer can only serialize Entity objects' );
		}

		$serializer = $this->getSerializer( $entity->getType() );

		return $serializer->getSerialized( $entity );
	}

	/**
	 * Constructs the original object from the provided serialization.
	 *
	 * @since 0.4
	 *
	 * @param array $serialization
	 *
	 * @throws InvalidArgumentException
	 * @return Entity
	 */
	public function newFromSerialization( array $serialization ) {
		if ( !isset( $serialization['type'] ) ) {
			throw new InvalidArgumentException( '$serialization[\'type\'] must be set' );
		}

		$entityType = $serialization['type'];
		$serializer = $this->getUnserializer( $entityType );

		return $serializer->newFromSerialization( $serialization );
	}

	/**
	 * @param string $entityType
	 * @return Serializer
	 */
	protected function getSerializer( $entityType ) {
		if ( !isset( $this->serializers[$entityType] ) ) {
			$this->serializers[$entityType] = $this->serializerFactory->newSerializerForEntity( $entityType, $this->options );
		}

		return $this->serializers[$entityType];
	}

	/**
	 * @param string $entityType
	 * @return Unserializer
	 */
	protected function getUnserializer( $entityType ) {
		if ( !isset( $this->unserializers[$entityType] ) ) {
			$this->unserializers[$entityType] = $this->serializerFactory->newUnserializerForEntity( $entityType, $this->options );
		}

		return $this->unserializers[$entityType];
	}
}
