<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SnakDeserializer implements Deserializer {

	private $TYPE_POSITION = 0;
	private $PROPERTY_ID_POSITION = 1;

	/**
	 * @see Deserializer::isDeserializerFor
	 *
	 * @param mixed $serialization
	 *
	 * @return boolean
	 */
	public function isDeserializerFor( $serialization ) {
		return is_array( $serialization )
			&& count( $serialization ) > 1
			&& $this->isSnakType( $serialization[$this->TYPE_POSITION] )
			&& $this->isPropertyId( $serialization[$this->PROPERTY_ID_POSITION] )
			&& $this->hasCorrectElementCount( $serialization );
	}

	private function isSnakType( $value ) {
		return in_array( $value, array( 'novalue', 'somevalue', 'value' ) );
	}

	private function isPropertyId( $value ) {
		return is_int( $value );
	}

	private function hasCorrectElementCount( array $serialization ) {
		$expectedCount = $serialization[$this->TYPE_POSITION] === 'value' ? 4 : 2;
		return count( $serialization ) == $expectedCount;
	}

	/**
	 * @see Deserializer::deserialize
	 *
	 * @param mixed $serialization
	 *
	 * @return object
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		// TODO
	}

}