<?php

namespace Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\InvalidAttributeException;
use Deserializers\Exceptions\MissingAttributeException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyDeserializer implements Deserializer {

	private $idDeserializer;
	private $termsDeserializer;

	/**
	 * @var Property
	 */
	private $property;
	private $serialization;

	public function __construct( Deserializer $idDeserializer, Deserializer $termsDeserializer ) {
		$this->idDeserializer = $idDeserializer;
		$this->termsDeserializer = $termsDeserializer;
	}

	/**
	 * @param mixed $serialization
	 *
	 * @return Property
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'Item serialization should be an array' );
		}

		$this->serialization = $serialization;
		$this->property = Property::newFromType( $this->getDataTypeId() );

		return $this->property;
	}

	private function getDataTypeId() {
		if ( !array_key_exists( 'datatype', $this->serialization ) ) {
			throw new MissingAttributeException( 'datatype' );
		}

		if ( !is_string( $this->serialization['datatype'] ) ) {
			throw new InvalidAttributeException(
				'datatype',
				$this->serialization['datatype'],
				'The datatype key should point to a string'
			);
		}

		return $this->serialization['datatype'];
	}



}