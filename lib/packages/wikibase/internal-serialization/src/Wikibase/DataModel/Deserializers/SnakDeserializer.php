<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use LogicException;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;

/**
 * TODO: modify namespace
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SnakDeserializer implements Deserializer {

	private $TYPE_POSITION = 0;
	private $PROPERTY_ID_POSITION = 1;

	private $dataValueDeserializer;

	public function __construct( Deserializer $dataValueDeserializer ) {
		$this->dataValueDeserializer = $dataValueDeserializer;
	}

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
		return in_array( $value, array( 'value', 'novalue', 'somevalue' ) );
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
	 * @throws LogicException
	 */
	public function deserialize( $serialization ) {
		if ( !$this->isDeserializerFor( $serialization ) ) {
			throw new DeserializationException();
		}

		switch ( $serialization[$this->TYPE_POSITION] ) {
			case 'value':
				return $this->newValueSnak( $serialization );
			case 'novalue':
				return $this->newNoValueSnak( $serialization );
			case 'somevalue':
				return $this->newSomeValueSnak( $serialization );
			default:
				throw new LogicException();
		}
	}

	private function newNoValueSnak( array $serialization ) {
		return new PropertyNoValueSnak( $serialization[$this->PROPERTY_ID_POSITION] );
	}

	private function newSomeValueSnak( array $serialization ) {
		return new PropertySomeValueSnak( $serialization[$this->PROPERTY_ID_POSITION] );
	}

	private function newValueSnak( array $serialization ) {
		return new PropertyValueSnak(
			$serialization[$this->PROPERTY_ID_POSITION],
			$this->newDataValue( $serialization[2], $serialization[3] )
		);
	}

	private function newDataValue( $type, $value ) {
		return $this->dataValueDeserializer->deserialize(
			array(
				'type' => $type,
				'value' => $value
			)
		);
	}

}