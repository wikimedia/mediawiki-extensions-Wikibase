<?php

namespace Wikibase\Repo\Content;

use InvalidArgumentException;
use RuntimeException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityContentDataCodec;

/**
 * EntityHolder implementing deferred deserialization.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
class DeferredDecodingEntityHolder implements EntityHolder {

	/**
	 * @var EntityDocument|null
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
	 * @var string|null
	 */
	private $contentFormat;

	/**
	 * @var string
	 */
	private $entityType;

	/**
	 * @var EntityId|null
	 */
	private $entityId = null;

	/**
	 * @param EntityContentDataCodec $codec
	 * @param string $blob
	 * @param string|null $contentFormat Serialization format to decode the blob, typically
	 *  CONTENT_FORMAT_JSON.
	 * @param string $entityType
	 * @param EntityId|null $entityId
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		EntityContentDataCodec $codec,
		$blob,
		$contentFormat,
		$entityType,
		EntityId $entityId = null
	) {
		if ( !is_string( $blob ) ) {
			throw new InvalidArgumentException( '$blob must be a string' );
		}

		if ( $contentFormat !== null && !is_string( $contentFormat ) ) {
			throw new InvalidArgumentException( '$contentFormat must be a string or null' );
		}

		if ( !is_string( $entityType ) ) {
			throw new InvalidArgumentException( '$entityType must be a string' );
		}

		$this->codec = $codec;
		$this->blob = $blob;
		$this->contentFormat = $contentFormat;
		$this->entityType = $entityType;
		$this->entityId = $entityId;
	}

	/**
	 * @see EntityHolder::getEntityId
	 *
	 * This implements lazy deserialization of the blob passed to the constructor.
	 *
	 * @param string $expectedClass The class with which the result is expected to be compatible.
	 * Defaults to EntityDocument.
	 *
	 * @throws RuntimeException If the entity held by this EntityHolder is not compatible with $expectedClass.
	 * @return EntityDocument
	 */
	public function getEntity( $expectedClass = EntityDocument::class ) {
		if ( !$this->entity ) {
			$this->entity = $this->codec->decodeEntity( $this->blob, $this->contentFormat );

			if ( !( $this->entity instanceof EntityDocument ) ) {
				throw new RuntimeException( 'Deferred decoding resulted in an incompatible entity, '
					. 'expected EntityDocument, got ' . gettype( $this->entity ) );
			} elseif ( $this->entity->getType() !== $this->entityType ) {
				throw new RuntimeException( 'Deferred decoding resulted in an incompatible entity, '
					. 'expected ' . $this->entityType . ', got ' . $this->entity->getType() );
			} elseif ( $this->entity->getId() === null ) {
				throw new RuntimeException( 'Deferred decoding resulted in an incompatible entity, '
					. 'expected an entity id to be set, got null' );
			} elseif ( $this->entityId && !$this->entity->getId()->equals( $this->entityId ) ) {
				throw new RuntimeException( 'Deferred decoding resulted in an incompatible entity, '
					. 'expected ' . $this->entityId . ', got ' . $this->entity->getId() );
			}
		}

		if ( !( $this->entity instanceof $expectedClass ) ) {
			throw new RuntimeException( 'Deferred decoding resulted in an incompatible entity, '
				. 'expected ' . $expectedClass . ', got ' . get_class( $this->entity ) );
		}

		return $this->entity;
	}

	/**
	 * @see EntityHolder::getEntityId
	 *
	 * @throws RuntimeException If the entity held by this EntityHolder does not have an id.
	 * @return EntityId
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
