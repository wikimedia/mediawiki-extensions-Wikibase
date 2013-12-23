<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\InvalidAttributeException;
use Deserializers\Exceptions\MissingAttributeException;
use Deserializers\Exceptions\MissingTypeException;
use Deserializers\Exceptions\UnsupportedTypeException;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thomas Pellissier Tanon
 */
class SnakDeserializer implements Deserializer {

	/**
	 * @var Deserializer
	 */
	private $dataValueDeserializer;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @param Deserializer $dataValueDeserializer
	 * @param EntityIdParser $entityIdParser
	 */
	public function __construct( Deserializer $dataValueDeserializer, EntityIdParser $entityIdParser ) {
		$this->dataValueDeserializer = $dataValueDeserializer;
		$this->entityIdParser = $entityIdParser;
	}

	/**
	 * @see Deserializer::isDeserializerFor
	 *
	 * @param mixed $serialization
	 *
	 * @return boolean
	 */
	public function isDeserializerFor( $serialization ) {
		return $this->hasSnakType( $serialization ) && $this->hasCorrectSnakType( $serialization );
	}

	private function hasSnakType( $serialization ) {
		return is_array( $serialization ) && array_key_exists( 'snaktype', $serialization );
	}

	private function hasCorrectSnakType( $serialization ) {
		return in_array( $serialization['snaktype'], array( 'novalue', 'somevalue', 'value' ) );
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
		$this->assertCanDeserialize( $serialization );
		$this->requireAttribute( $serialization, 'property' );

		return $this->getDeserialized( $serialization );
	}

	private function getDeserialized( array $serialization ) {
		switch ( $serialization['snaktype'] ) {
			case 'value':
				return $this->newValueSnak( $serialization );
			case 'novalue':
				return $this->newNoValueSnak( $serialization );
			case 'somevalue':
				return $this->newSomeValueSnak( $serialization );
		}
	}

	private function newNoValueSnak( array $serialization ) {
		return new PropertyNoValueSnak( $this->parsePropertyId( $serialization['property'] ) );
	}

	private function newSomeValueSnak( array $serialization ) {
		return new PropertySomeValueSnak( $this->parsePropertyId( $serialization['property'] ) );
	}

	private function newValueSnak( array $serialization ) {
		$this->requireAttribute( $serialization, 'datavalue' );

		return new PropertyValueSnak(
			$this->parsePropertyId( $serialization['property'] ),
			$this->dataValueDeserializer->deserialize( $serialization['datavalue'] )
		);
	}

	private function parsePropertyId( $propertyId ) {
		try {
			return $this->entityIdParser->parse( $propertyId );
		} catch ( EntityIdParsingException $e ) {
			throw new InvalidAttributeException(
				'property',
				$propertyId,
				"'$propertyId' is not a valid property ID",
				$e
			);
		}
	}

	private function assertCanDeserialize( $serialization ) {
		if ( !$this->hasSnakType( $serialization ) ) {
			throw new MissingTypeException();
		}

		if ( !$this->hasCorrectSnakType( $serialization ) ) {
			throw new UnsupportedTypeException( $serialization['snaktype'] );
		}
	}


	protected function requireAttribute( array $array, $attributeName ) {
		if ( !array_key_exists( $attributeName, $array ) ) {
			throw new MissingAttributeException(
				$attributeName
			);
		}
	}
}
