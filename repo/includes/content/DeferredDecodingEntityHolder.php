<?php

namespace Wikibase\Content;

use InvalidArgumentException;
use RuntimeException;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityContentDataCodec;

/**
 * EntityHolder implementing deferred deserialization.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class DeferredDecodingEntityHolder implements EntityHolder {

	/**
	 * @var Entity
	 */
	private $entity = null;

	/**
	 * @var EntityContentDataCodec
	 */
	private $codec;

	/**
	 * @var string
	 */
	private $blob;

	/**
	 * @var null
	 */
	private $format;

	/**
	 * @var string
	 */
	private $entityType;

	/**
	 * @var EntityId
	 */
	private $entityId;

	/**
	 * @param EntityContentDataCodec $codec
	 * @param string $blob
	 * @param string $format
	 * @param $entityType
	 * @param EntityId $entityId
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( EntityContentDataCodec $codec, $blob, $format, $entityType, EntityId $entityId = null ) {
		if ( !is_string( $blob ) ) {
			throw new InvalidArgumentException( '$blob must be a string' );
		}

		if ( !is_string( $format ) ) {
			throw new InvalidArgumentException( '$format must be a string' );
		}

		if ( !is_string( $entityType ) ) {
			throw new InvalidArgumentException( '$entityType must be a string' );
		}

		$this->codec = $codec;
		$this->blob = $blob;
		$this->format = $format;
		$this->entityType = $entityType;
		$this->entityId = $entityId;
	}

	/**
	 * @see EntityHolder::getEntityId
	 *
	 * This implements lazy initialization of the entity: when called for the first time,
	 * this method will call getEntity() on the EntityHolder passed to the constructor,
	 * and then calls copy() on the entity returned. The resulting copy is returned.
	 * Subsequent calls will return the same entity.
	 *
	 * @param string $expectedClass The class the result is expected to be compatible with.
	 * Defaults to Entity.
	 *
	 * @throws RuntimeException If the entity held by this EntityHolder is not compatible with $expectedClass.
	 * @return Entity
	 */
	public function getEntity( $expectedClass = 'Wikibase\DataModel\Entity\Entity' ) {
		if ( !$this->entity ) {
			$this->entity = $this->codec->decodeEntity( $this->blob, $this->format );
		}

		return $this->entity;
	}

	/**
	 * @see EntityHolder::getEntityId
	 *
	 * @return EntityId|null
	 */
	public function getEntityId() {
		if ( !$this->entityId ) {
			$this->entityId = $this->getEntity()->getId();
		}

		return $this->entityId;
	}

	/**
	 * @see EntityHolder::getEntityType
	 *
	 * @return string
	 */
	public function getEntityType() {
		return $this->entityType;
	}

}
 