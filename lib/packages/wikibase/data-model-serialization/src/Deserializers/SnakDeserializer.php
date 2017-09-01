<?php

namespace Wikibase\DataModel\Deserializers;

use DataValues\UnDeserializableValue;
use Deserializers\Deserializer;
use Deserializers\DispatchableDeserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\InvalidAttributeException;
use Deserializers\Exceptions\MissingAttributeException;
use Deserializers\Exceptions\MissingTypeException;
use Deserializers\Exceptions\UnsupportedTypeException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;

/**
 * Package private
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thomas Pellissier Tanon
 */
class SnakDeserializer implements DispatchableDeserializer {

	/**
	 * @var Deserializer
	 */
	private $dataValueDeserializer;

	/**
	 * @var Deserializer
	 */
	private $entityIdDeserializer;

	public function __construct(
		Deserializer $dataValueDeserializer,
		Deserializer $entityIdDeserializer
	) {
		$this->dataValueDeserializer = $dataValueDeserializer;
		$this->entityIdDeserializer = $entityIdDeserializer;
	}

	/**
	 * @see Deserializer::isDeserializerFor
	 *
	 * @param mixed $serialization
	 *
	 * @return bool
	 */
	public function isDeserializerFor( $serialization ) {
		return is_array( $serialization )
			&& $this->hasSnakType( $serialization )
			&& $this->hasCorrectSnakType( $serialization );
	}

	private function hasSnakType( $serialization ) {
		return array_key_exists( 'snaktype', $serialization );
	}

	private function hasCorrectSnakType( $serialization ) {
		return in_array( $serialization['snaktype'], [ 'novalue', 'somevalue', 'value' ] );
	}

	/**
	 * @see Deserializer::deserialize
	 *
	 * @param array $serialization
	 *
	 * @throws DeserializationException
	 * @return PropertyNoValueSnak|PropertySomeValueSnak|PropertyValueSnak
	 */
	public function deserialize( $serialization ) {
		$this->assertCanDeserialize( $serialization );
		$this->requireAttribute( $serialization, 'property' );

		return $this->getDeserialized( $serialization );
	}

	/**
	 * @see SnakDeserializer::hasCorrectSnakType
	 *
	 * @param array $serialization
	 *
	 * @throws InvalidAttributeException
	 * @return PropertyNoValueSnak|PropertySomeValueSnak|PropertyValueSnak
	 */
	private function getDeserialized( array $serialization ) {
		switch ( $serialization['snaktype'] ) {
			case 'novalue':
				return $this->newNoValueSnak( $serialization );
			case 'somevalue':
				return $this->newSomeValueSnak( $serialization );
			default:
				return $this->newValueSnak( $serialization );
		}
	}

	private function newNoValueSnak( array $serialization ) {
		return new PropertyNoValueSnak( $this->deserializePropertyId( $serialization['property'] ) );
	}

	private function newSomeValueSnak( array $serialization ) {
		return new PropertySomeValueSnak( $this->deserializePropertyId( $serialization['property'] ) );
	}

	private function newValueSnak( array $serialization ) {
		$this->requireAttribute( $serialization, 'datavalue' );

		return new PropertyValueSnak(
			$this->deserializePropertyId( $serialization['property'] ),
			$this->deserializeDataValue( $serialization['datavalue'] )
		);
	}

	private function deserializeDataValue( $serialization ) {
		try {
			return $this->dataValueDeserializer->deserialize( $serialization );
		} catch ( DeserializationException $ex ) {
			$error = isset( $serialization['error'] ) ? $serialization['error'] : $ex->getMessage();
			return new UnDeserializableValue( $serialization['value'], $serialization['type'], $error );
		}
	}

	/**
	 * @param string $serialization
	 *
	 * @throws InvalidAttributeException
	 * @return PropertyId
	 */
	private function deserializePropertyId( $serialization ) {
		$propertyId = $this->entityIdDeserializer->deserialize( $serialization );

		if ( !( $propertyId instanceof PropertyId ) ) {
			throw new InvalidAttributeException(
				'property',
				$serialization,
				"'$serialization' is not a valid property ID"
			);
		}

		return $propertyId;
	}

	private function assertCanDeserialize( $serialization ) {
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'The snak serialization should be an array' );
		}

		if ( !$this->hasSnakType( $serialization ) ) {
			throw new MissingTypeException();
		}

		if ( !$this->hasCorrectSnakType( $serialization ) ) {
			throw new UnsupportedTypeException( $serialization['snaktype'] );
		}
	}

	private function requireAttribute( array $array, $attributeName ) {
		if ( !array_key_exists( $attributeName, $array ) ) {
			throw new MissingAttributeException(
				$attributeName
			);
		}
	}

}
