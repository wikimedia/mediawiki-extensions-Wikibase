<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\TypedObjectDeserializer;
use Wikibase\DataModel\Entity\Property;

/**
 * Package private
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class PropertyDeserializer extends TypedObjectDeserializer {

	/**
	 * @var Deserializer
	 */
	private $entityIdDeserializer;

	/**
	 * @var Deserializer
	 */
	private $fingerprintDeserializer;

	/**
	 * @var Deserializer
	 */
	private $statementListDeserializer;

	/**
	 * @param Deserializer $entityIdDeserializer
	 * @param Deserializer $fingerprintDeserializer
	 * @param Deserializer $statementListDeserializer
	 */
	public function __construct(
		Deserializer $entityIdDeserializer,
		Deserializer $fingerprintDeserializer,
		Deserializer $statementListDeserializer
	) {
		parent::__construct( 'property', 'type' );

		$this->entityIdDeserializer = $entityIdDeserializer;
		$this->fingerprintDeserializer = $fingerprintDeserializer;
		$this->statementListDeserializer = $statementListDeserializer;
	}

	/**
	 * @see Deserializer::deserialize
	 *
	 * @param array $serialization
	 *
	 * @return Property
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		$this->assertCanDeserialize( $serialization );

		return $this->getDeserialized( $serialization );
	}

	private function getDeserialized( array $serialization ) {
		$this->requireAttribute( $serialization, 'datatype' );
		$this->assertAttributeInternalType( $serialization, 'datatype', 'string' );

		$property = Property::newFromType( $serialization['datatype'] );

		$property->setFingerprint( $this->fingerprintDeserializer->deserialize( $serialization ) );

		$this->setIdFromSerialization( $serialization, $property );
		$this->setStatementListFromSerialization( $serialization, $property );

		return $property;
	}

	private function setIdFromSerialization( array $serialization, Property $property ) {
		if ( !array_key_exists( 'id', $serialization ) ) {
			return;
		}

		$property->setId( $this->entityIdDeserializer->deserialize( $serialization['id'] ) );
	}

	private function setStatementListFromSerialization( array $serialization, Property $property ) {
		if ( !array_key_exists( 'claims', $serialization ) ) {
			return;
		}

		$statements = $this->statementListDeserializer->deserialize( $serialization['claims'] );
		$property->setStatements( $statements );
	}

}
