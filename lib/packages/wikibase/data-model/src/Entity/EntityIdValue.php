<?php

namespace Wikibase\DataModel\Entity;

use DataValues\DataValueObject;
use DataValues\IllegalValueException;
use InvalidArgumentException;
use Wikibase\DataModel\LegacyIdInterpreter;

/**
 * @since 0.5
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Kreuz
 * @author Daniel Kinzler
 */
class EntityIdValue extends DataValueObject {

	private $entityId;

	public function __construct( EntityId $entityId ) {
		$this->entityId = $entityId;
	}

	/**
	 * @return string
	 */
	public function getHash(): string {
		return md5( $this->getSerializationForHash() );
	}

	/**
	 * The serialization to use for hashing, for compatibility reasons this is
	 * equivalent to the old (pre 7.4) PHP serialization.
	 *
	 * @return string
	 */
	public function getSerializationForHash(): string {
		$data = $this->entityId->serialize();
		$innerSerialization = 'C:' . strlen( get_class( $this->entityId ) ) . ':"' . get_class( $this->entityId ) .
		'":' . strlen( $data ) . ':{' . $data . '}';

		return 'C:' . strlen( static::class ) . ':"' . static::class .
			'":' . strlen( $innerSerialization ) . ':{' . $innerSerialization . '}';
	}

	public function __serialize(): array {
		return [ 'entityId' => $this->entityId ];
	}

	/**
	 * @see Serializable::serialize
	 *
	 * @since 7.0 serialization format changed in an incompatible way
	 *
	 * @note Do not use PHP serialization for persistence! Use a DataValueSerializer instead.
	 *
	 * @return string
	 */
	public function serialize() {
		return serialize( $this->entityId );
	}

	public function __unserialize( array $data ): void {
		$this->__construct( $data['entityId'] );
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @param string $serialized
	 *
	 * @throws IllegalValueException
	 */
	public function unserialize( $serialized ) {
		$array = json_decode( $serialized );

		if ( !is_array( $array ) ) {
			$this->__construct( unserialize( $serialized ) );
			return;
		}

		list( $entityType, $numericId ) = $array;

		try {
			$entityId = LegacyIdInterpreter::newIdFromTypeAndNumber( $entityType, $numericId );
		} catch ( InvalidArgumentException $ex ) {
			throw new IllegalValueException( 'Invalid EntityIdValue serialization.', 0, $ex );
		}

		$this->__construct( $entityId );
	}

	/**
	 * @see DataValue::getType
	 *
	 * @return string
	 */
	public static function getType() {
		return 'wikibase-entityid';
	}

	/**
	 * @deprecated Kept for compatibility with older DataValues versions.
	 * Do not use.
	 *
	 * @return string|float|int
	 */
	public function getSortKey() {
		return $this->entityId->getSerialization();
	}

	/**
	 * @see DataValue::getValue
	 *
	 * @return self
	 */
	public function getValue() {
		return $this;
	}

	/**
	 * @return EntityId
	 */
	public function getEntityId() {
		return $this->entityId;
	}

	/**
	 * @see DataValue::getArrayValue
	 *
	 * @return array
	 */
	public function getArrayValue() {
		$array = [
			'entity-type' => $this->entityId->getEntityType(),
		];

		if ( $this->entityId instanceof Int32EntityId ) {
			$array['numeric-id'] = $this->entityId->getNumericId();
		}

		$array['id'] = $this->entityId->getSerialization();
		return $array;
	}

	/**
	 * Constructs a new instance from the provided data. Required for @see DataValueDeserializer.
	 * This is expected to round-trip with @see getArrayValue.
	 *
	 * @deprecated since 7.1. Static DataValue::newFromArray constructors like this are
	 *  underspecified (not in the DataValue interface), and misleadingly named (should be named
	 *  newFromArrayValue). Instead, use DataValue builder callbacks in @see DataValueDeserializer.
	 *
	 * @param mixed $data Warning! Even if this is expected to be a value as returned by
	 *  @see getArrayValue, callers of this specific newFromArray implementation can not guarantee
	 *  this. This is not even guaranteed to be an array!
	 *
	 * @throws IllegalValueException if $data is not in the expected format. Subclasses of
	 *  InvalidArgumentException are expected and properly handled by @see DataValueDeserializer.
	 * @return self
	 */
	public static function newFromArray( $data ) {
		if ( !is_array( $data ) ) {
			throw new IllegalValueException( '$data must be an array' );
		}

		if ( array_key_exists( 'entity-type', $data ) && array_key_exists( 'numeric-id', $data ) ) {
			return self::newIdFromTypeAndNumber( $data['entity-type'], $data['numeric-id'] );
		} elseif ( array_key_exists( 'id', $data ) ) {
			throw new IllegalValueException(
				'Not able to parse "id" strings, use callbacks in DataValueDeserializer instead'
			);
		}

		throw new IllegalValueException( 'Either "id" or "entity-type" and "numeric-id" fields required' );
	}

	/**
	 * @param string $entityType
	 * @param int|float|string $numericId
	 *
	 * @throws IllegalValueException
	 * @return self
	 */
	private static function newIdFromTypeAndNumber( $entityType, $numericId ) {
		try {
			return new self( LegacyIdInterpreter::newIdFromTypeAndNumber( $entityType, $numericId ) );
		} catch ( InvalidArgumentException $ex ) {
			throw new IllegalValueException( $ex->getMessage(), 0, $ex );
		}
	}

}
