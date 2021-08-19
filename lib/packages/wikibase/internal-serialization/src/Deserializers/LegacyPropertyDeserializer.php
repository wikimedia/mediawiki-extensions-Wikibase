<?php

namespace Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\DispatchableDeserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\InvalidAttributeException;
use Deserializers\Exceptions\MissingAttributeException;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class LegacyPropertyDeserializer implements DispatchableDeserializer {

	/**
	 * @var Deserializer
	 */
	private $idDeserializer;

	/**
	 * @var Deserializer
	 */
	private $fingerprintDeserializer;

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
			throw new DeserializationException( 'Property serialization should be an array' );
		}

		return new Property(
			$this->getPropertyId( $serialization ),
			$this->fingerprintDeserializer->deserialize( $serialization ),
			$this->getDataTypeId( $serialization )
		);
	}

	/**
	 * @param array $serialization
	 *
	 * @return PropertyId|null
	 * @throws InvalidAttributeException
	 */
	private function getPropertyId( array $serialization ) {
		if ( array_key_exists( 'entity', $serialization ) ) {
			$id = $this->idDeserializer->deserialize( $serialization['entity'] );

			if ( !( $id instanceof PropertyId ) ) {
				throw new InvalidAttributeException(
					'entity',
					$serialization['entity'],
					'Properties should have a property id'
				);
			}

			return $id;
		}

		return null;
	}

	/**
	 * @param array $serialization
	 *
	 * @return string
	 * @throws MissingAttributeException
	 * @throws InvalidAttributeException
	 */
	private function getDataTypeId( array $serialization ) {
		if ( !array_key_exists( 'datatype', $serialization ) ) {
			throw new MissingAttributeException( 'datatype' );
		}

		if ( !is_string( $serialization['datatype'] ) ) {
			throw new InvalidAttributeException(
				'datatype',
				$serialization['datatype'],
				'The datatype key should point to a string'
			);
		}

		return $serialization['datatype'];
	}

	/**
	 * @see DispatchableDeserializer::isDeserializerFor
	 *
	 * @since 2.2
	 *
	 * @param mixed $serialization
	 *
	 * @return bool
	 */
	public function isDeserializerFor( $serialization ) {
		return is_array( $serialization )
			// This element is called 'id' in the current serialization.
			&& array_key_exists( 'entity', $serialization )
			&& array_key_exists( 'datatype', $serialization );
	}

}
