<?php

namespace Wikibase\DataModel\Entity;

use InvalidArgumentException;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com
 */
class ItemId extends EntityId {

	const PATTERN = '/^Q[1-9]\d*$/i';

	/**
	 * @param string $idSerialization
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $idSerialization ) {
		$this->assertValidIdFormat( $idSerialization );
		$this->serialization = strtoupper( $idSerialization );
	}

	private function assertValidIdFormat( $idSerialization ) {
		if ( !is_string( $idSerialization ) ) {
			throw new InvalidArgumentException( 'The id serialization needs to be a string.' );
		}

		if ( !preg_match( self::PATTERN, $idSerialization ) ) {
			throw new InvalidArgumentException( 'Invalid ItemId serialization provided.' );
		}
	}

	/**
	 * @return int
	 */
	public function getNumericId() {
		return (int)substr( $this->serialization, 1 );
	}

	/**
	 * @return string
	 */
	public function getEntityType() {
		return 'item';
	}

	/**
	 * @see Serializable::serialize
	 *
	 * @return string
	 */
	public function serialize() {
		return json_encode( array( 'item', $this->serialization ) );
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @param string $value
	 */
	public function unserialize( $value ) {
		list( , $this->serialization ) = json_decode( $value );
	}

	/**
	 * Construct an ItemId given the numeric part of its serialization.
	 *
	 * CAUTION: new usages of this method are discouraged. Typically you
	 * should avoid dealing with just the numeric part, and use the whole
	 * serialization. Not doing so in new code requires special justification.
	 *
	 * @param int|float $number
	 *
	 * @return ItemId
	 * @throws InvalidArgumentException
	 */
	public static function newFromNumber( $number ) {
		if ( !is_int( $number ) && !is_float( $number ) ) {
			throw new InvalidArgumentException( '$number needs to be an integer or whole number float.' );
		}

		return new self( 'Q' . $number );
	}

}
