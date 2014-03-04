<?php

namespace Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Exception;
use Wikibase\DataModel\Snak\Snak;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SnakDeserializer implements Deserializer {

	/**
	 * @param mixed $serialization
	 *
	 * @return Snak
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		// TODO: Implement deserialize() method.
	}

	/**
	 * @since 1.0
	 *
	 * @param mixed $serialization
	 *
	 * @return boolean
	 */
	public function isDeserializerFor( $serialization ) {
		try {
			$this->assertStructureIsValid( $serialization );
			return true;
		}
		catch ( Exception $e ) {
			return false;
		}
	}

	private function assertStructureIsValid( $serialization ) {
		if ( !is_array( $serialization ) || $serialization === array() ) {
			throw new DeserializationException( 'Serialization should be a non-empty array' );
		}

		if ( $serialization[0] === 'value' ) {
			$this->assertIsValueSnak( $serialization );
		}
		else {
			$this->assertIsNonValueSnak( $serialization );
		}

		$this->assertIsPropertyId( $serialization[1] );
	}

	private function assertIsValueSnak( array $serialization ) {
		if ( count( $serialization ) != 4 ) {
			throw new DeserializationException( 'Value snaks need to have 4 elements' );
		}
	}

	private function assertIsNonValueSnak( array $serialization ) {
		if ( count( $serialization ) != 2 ) {
			throw new DeserializationException( 'Non-value snaks need to have 2 elements' );
		}

		if ( !in_array( $serialization[0], array( 'novalue', 'somevalue' ) ) ) {
			throw new DeserializationException( 'Unknown snak type' );
		}
	}

	private function assertIsPropertyId( $idSerialization ) {
		if ( !is_int( $idSerialization ) || $idSerialization < 1 ) {
			throw new DeserializationException( 'Property id needs to be an int bigger than 0' );
		}
	}

}