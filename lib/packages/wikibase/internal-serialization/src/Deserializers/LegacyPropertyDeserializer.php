<?php

namespace Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\InvalidAttributeException;
use Deserializers\Exceptions\MissingAttributeException;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Fingerprint;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LegacyPropertyDeserializer implements Deserializer {

	private $idDeserializer;
	private $fingerprintDeserializer;

	/**
	 * @var Property
	 */
	private $property;
	private $serialization;

	public function __construct( Deserializer $idDeserializer, Deserializer $fingerprintDeserializer ) {
		$this->idDeserializer = $idDeserializer;
		$this->fingerprintDeserializer = $fingerprintDeserializer;
	}

	/**
	 * @param array $serialization
	 *
	 * @return Property
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'Item serialization should be an array' );
		}

		$this->serialization = $serialization;
		$this->property = new Property(
			$this->getPropertyId(),
			$this->getFingerprint(),
			$this->getDataTypeId()
		);

		return $this->property;
	}

	/**
	 * @throws MissingAttributeException
	 * @throws InvalidAttributeException
	 * @return string
	 */
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

	/**
	 * @throws InvalidAttributeException
	 * @return PropertyId|null
	 */
	private function getPropertyId() {
		if ( array_key_exists( 'entity', $this->serialization ) ) {
			$id = $this->idDeserializer->deserialize( $this->serialization['entity'] );

			if ( !( $id instanceof PropertyId ) ) {
				throw new InvalidAttributeException(
					'entity',
					$this->serialization['entity'],
					'Properties should have a property id'
				);
			}

			return $id;
		}

		return null;
	}

	/**
	 * @return Fingerprint
	 */
	private function getFingerprint() {
		return $this->fingerprintDeserializer->deserialize( $this->serialization );
	}

}
