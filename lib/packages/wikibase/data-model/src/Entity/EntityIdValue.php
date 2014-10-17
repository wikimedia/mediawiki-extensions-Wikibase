<?php

namespace Wikibase\DataModel\Entity;

use DataValues\DataValue;
use DataValues\DataValueObject;
use DataValues\IllegalValueException;
use InvalidArgumentException;
use Wikibase\DataModel\LegacyIdInterpreter;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com
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
	 * @return double Numeric id as a whole number. Can not be int because of 32-bit PHP.
	 */
	protected function getNumericId() {
		return doubleval( substr( $this->entityId->getSerialization(), 1 ) );
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @since 0.5
	 *
	 * @param string $value
	 *
	 * @throws IllegalValueException
	 */
	public function unserialize( $value ) {
		list( $entityType, $numericId ) = json_decode( $value );

		try {
			$entityId = LegacyIdInterpreter::newIdFromTypeAndNumber( $entityType, $numericId );
		} catch ( InvalidArgumentException $ex ) {
			throw new IllegalValueException( 'Invalid EntityIdValue serialization.' );
		}

		return $this->__construct( $entityId );
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
	 * @return EntityId
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
	 * @return EntityId
	 */
	public function getArrayValue() {
		return array(
			'entity-type' => $this->entityId->getEntityType(),
			'numeric-id' => $this->getNumericId(),
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
	 * @return DataValue
	 */
	public static function newFromArray( $data ) {
		if ( !is_array( $data ) ) {
			throw new IllegalValueException( "array expected" );
		}

		if ( !array_key_exists( 'entity-type', $data ) ) {
			throw new IllegalValueException( "'entity-type' field required" );
		}

		if ( !array_key_exists( 'numeric-id', $data ) ) {
			throw new IllegalValueException( "'numeric-id' field required" );
		}

		try {
			$id = LegacyIdInterpreter::newIdFromTypeAndNumber(
				$data['entity-type'],
				$data['numeric-id']
			);
		}
		catch ( \InvalidArgumentException $ex ) {
			throw new IllegalValueException( $ex->getMessage(), 0, $ex );
		}

		return new static( $id );
	}

}
