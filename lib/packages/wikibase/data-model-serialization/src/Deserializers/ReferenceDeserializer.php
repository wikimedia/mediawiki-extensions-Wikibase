<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\DispatchableDeserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\InvalidAttributeException;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\SnakList;

/**
 * Package private
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class ReferenceDeserializer implements DispatchableDeserializer {

	/**
	 * @var Deserializer
	 */
	private $snaksDeserializer;

	public function __construct( Deserializer $snaksDeserializer ) {
		$this->snaksDeserializer = $snaksDeserializer;
	}

	/**
	 * @see Deserializer::isDeserializerFor
	 *
	 * @param mixed $serialization
	 *
	 * @return bool
	 */
	public function isDeserializerFor( $serialization ) {
		return $this->isValidSerialization( $serialization );
	}

	private function isValidSerialization( $serialization ) {
		return is_array( $serialization ) && array_key_exists( 'snaks', $serialization );
	}

	/**
	 * @see Deserializer::deserialize
	 *
	 * @param array $serialization
	 *
	 * @throws DeserializationException
	 * @return Reference
	 */
	public function deserialize( $serialization ) {
		if ( !$this->isValidSerialization( $serialization ) ) {
			throw new DeserializationException( 'The serialization is invalid' );
		}

		return $this->getDeserialized( $serialization );
	}

	/**
	 * @param array $serialization
	 *
	 * @return Reference
	 */
	private function getDeserialized( array $serialization ) {
		return new Reference(
			$this->deserializeSnaks( $serialization )
		);
	}

	/**
	 * @param array $serialization
	 *
	 * @return SnakList
	 */
	private function deserializeSnaks( array $serialization ) {
		$snaks = $this->snaksDeserializer->deserialize( $serialization['snaks'] );

		if ( array_key_exists( 'snaks-order', $serialization ) ) {
			$this->assertSnaksOrderIsArray( $serialization );

			$snaks->orderByProperty( $serialization['snaks-order'] );
		}

		return $snaks;
	}

	private function assertSnaksOrderIsArray( array $serialization ) {
		if ( !is_array( $serialization['snaks-order'] ) ) {
			throw new InvalidAttributeException(
				'snaks-order',
				$serialization['snaks-order'],
				"snaks-order attribute is not a valid array"
			);
		}
	}

}
