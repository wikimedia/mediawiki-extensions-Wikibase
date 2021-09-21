<?php

namespace Wikibase\InternalSerialization\Deserializers;

use DataValues\DataValue;
use DataValues\UnDeserializableValue;
use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use LogicException;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LegacySnakDeserializer implements Deserializer {

	/**
	 * @var Deserializer
	 */
	private $dataValueDeserializer;

	public function __construct( Deserializer $dataValueDeserializer ) {
		$this->dataValueDeserializer = $dataValueDeserializer;
	}

	/**
	 * @param array $serialization
	 *
	 * @return Snak
	 * @throws DeserializationException
	 * @throws LogicException
	 */
	public function deserialize( $serialization ) {
		$this->assertStructureIsValid( $serialization );

		switch ( $serialization[0] ) {
			case 'novalue':
				return new PropertyNoValueSnak( $serialization[1] );
			case 'somevalue':
				return new PropertySomeValueSnak( $serialization[1] );
			case 'value':
				return $this->deserializeValueSnak( $serialization );
				// @codeCoverageIgnoreStart
			default:
				throw new LogicException();
		}
		// @codeCoverageIgnoreEnd
	}

	private function deserializeValueSnak( array $serialization ) {
		try {
			$dataValue = $this->dataValueDeserializer->deserialize(
				[
					'type' => $serialization[2],
					'value' => $serialization[3],
				]
			);
		} catch ( DeserializationException $ex ) {
			$dataValue = new UnDeserializableValue( $serialization[3], $serialization[2], $ex->getMessage() );
		}

		/**
		 * @var DataValue $dataValue
		 */
		return new PropertyValueSnak( $serialization[1], $dataValue );
	}

	private function assertStructureIsValid( $serialization ) {
		if ( !is_array( $serialization ) || $serialization === [] ) {
			throw new DeserializationException( 'Serialization should be a non-empty array' );
		}

		if ( $serialization[0] === 'value' ) {
			$this->assertIsValueSnak( $serialization );
		} else {
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

		if ( !in_array( $serialization[0], [ 'novalue', 'somevalue' ] ) ) {
			throw new DeserializationException( 'Unknown snak type' );
		}
	}

	private function assertIsPropertyId( $idSerialization ) {
		if ( !is_int( $idSerialization ) || $idSerialization < 1 ) {
			throw new DeserializationException( 'Property id needs to be an int bigger than 0' );
		}
	}

}
