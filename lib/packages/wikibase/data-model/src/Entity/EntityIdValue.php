<?php

namespace Wikibase\DataModel\Entity;

use DataValues\DataValueObject;
use DataValues\IllegalValueException;
use InvalidArgumentException;
use Wikibase\DataModel\LegacyIdInterpreter;

/**
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Mättig
 */
class EntityIdValue extends DataValueObject {

	private $entityId;

	public function __construct( EntityId $entityId ) {
		$this->entityId = $entityId;
	}

	/**
	 * @see Serializable::serialize
	 *
	 * @since 0.5
	 *
	 * @return string
	 */
	public function serialize() {
		return json_encode( array(
			$this->entityId->getEntityType(),
			$this->getNumericId()
		) );
	}

	/**
	 * This method gets the numeric id from the serialization.
	 * It makes assumptions we do not want to make about the id format,
	 * though cannot be removed until we ditch the "numeric id" part
	 * from the serialization.
	 *
	 * @return float Numeric id as a whole number. Can not be int because of 32-bit PHP.
	 */
	private function getNumericId() {
		return floatval( substr( $this->entityId->getSerialization(), 1 ) );
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @since 0.5
	 *
	 * @param string $serialized
	 *
	 * @throws IllegalValueException
	 */
	public function unserialize( $serialized ) {
		list( $entityType, $numericId ) = json_decode( $serialized );

		try {
			$entityId = LegacyIdInterpreter::newIdFromTypeAndNumber( $entityType, $numericId );
		} catch ( InvalidArgumentException $ex ) {
			throw new IllegalValueException( 'Invalid EntityIdValue serialization.' );
		}

		$this->__construct( $entityId );
	}

	/**
	 * @see DataValue::getType
	 *
	 * @since 0.5
	 *
	 * @return string
	 */
	public static function getType() {
		return 'wikibase-entityid';
	}

	/**
	 * @see DataValue::getSortKey
	 *
	 * @since 0.5
	 *
	 * @return string|float|int
	 */
	public function getSortKey() {
		return $this->entityId->getSerialization();
	}

	/**
	 * @see DataValue::getValue
	 *
	 * @since 0.5
	 *
	 * @return self
	 */
	public function getValue() {
		return $this;
	}

	/**
	 * @since 0.5
	 *
	 * @return EntityId
	 */
	public function getEntityId() {
		return $this->entityId;
	}

	/**
	 * @see DataValue::getArrayValue
	 *
	 * @since 0.5
	 *
	 * @return array
	 */
	public function getArrayValue() {
		return array(
			'entity-type' => $this->entityId->getEntityType(),
			'numeric-id' => $this->getNumericId(),
			'id' => $this->entityId->getSerialization(),
		);
	}

	/**
	 * Constructs a new instance of the DataValue from the provided data.
	 * This can round-trip with
	 * @see getArrayValue
	 *
	 * @since 0.5
	 *
	 * @param mixed $data
	 *
	 * @throws IllegalValueException
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
