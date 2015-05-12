<?php

namespace Wikibase\Content;

use InvalidArgumentException;
use RuntimeException;
use Wikibase\DataModel\Entity\EntityDocument;
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
	 * @var string
	 */
	private $format;

	/**
	 * @var string
	 */
	private $entityType;

	/**
	 * @var EntityId|null
	 */
	private $entityId;

	/**
	 * @param EntityContentDataCodec $codec
	 * @param string $blob
	 * @param string $format Serialization format to decode the blob, typically CONTENT_FORMAT_JSON.
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
	 * This implements lazy deserialization of the blob passed to the constructor.
	 *
	 * @param string $expectedClass The class with which the result is expected to be compatible.
	 * Defaults to EntityDocument.
	 *
	 * @throws RuntimeException If the entity held by this EntityHolder is not compatible with $expectedClass.
	 * @return EntityDocument
	 */
	public function getEntity( $expectedClass = 'Wikibase\DataModel\Entity\EntityDocument' ) {
		if ( !$this->entity ) {
			$this->entity = $this->codec->decodeEntity( $this->blob, $this->format );

			if ( !( $this->entity instanceof $expectedClass ) ) {
				throw new RuntimeException( 'Deferred decoding resulted in an incompatible entity. ' .
					'Expected ' . $expectedClass . ', got ' . get_class(  ) );
			}
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
