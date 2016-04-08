<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\TypedObjectDeserializer;
use Wikibase\DataModel\Entity\Property;

/**
 * Package private
 *
 * @license GPL-2.0+
 * @author Thomas Pellissier Tanon
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class PropertyDeserializer extends TypedObjectDeserializer {

	/**
	 * @var Deserializer
	 */
	private $entityIdDeserializer;

	/**
	 * @var Deserializer
	 */
	private $termListDeserializer;

	/**
	 * @var Deserializer
	 */
	private $aliasGroupListDeserializer;

	/**
	 * @var Deserializer
	 */
	private $statementListDeserializer;

	/**
	 * @param Deserializer $entityIdDeserializer
	 * @param Deserializer $termListDeserializer
	 * @param Deserializer $aliasGroupListDeserializer
	 * @param Deserializer $statementListDeserializer
	 */
	public function __construct(
		Deserializer $entityIdDeserializer,
		Deserializer $termListDeserializer,
		Deserializer $aliasGroupListDeserializer,
		Deserializer $statementListDeserializer
	) {
		parent::__construct( 'property', 'type' );

		$this->entityIdDeserializer = $entityIdDeserializer;
		$this->termListDeserializer = $termListDeserializer;
		$this->aliasGroupListDeserializer = $aliasGroupListDeserializer;
		$this->statementListDeserializer = $statementListDeserializer;
	}

	/**
	 * @see Deserializer::deserialize
	 *
	 * @param array $serialization
	 *
	 * @throws DeserializationException
	 * @return Property
	 */
	public function deserialize( $serialization ) {
		$this->assertCanDeserialize( $serialization );

		return $this->getDeserialized( $serialization );
	}

	/**
	 * @param array $serialization
	 *
	 * @return Property
	 */
	private function getDeserialized( array $serialization ) {
		$this->requireAttribute( $serialization, 'datatype' );
		$this->assertAttributeInternalType( $serialization, 'datatype', 'string' );

		$property = Property::newFromType( $serialization['datatype'] );

		$this->setIdFromSerialization( $serialization, $property );
		$this->setTermsFromSerialization( $serialization, $property );
		$this->setStatementListFromSerialization( $serialization, $property );

		return $property;
	}

	private function setIdFromSerialization( array $serialization, Property $property ) {
		if ( !array_key_exists( 'id', $serialization ) ) {
			return;
		}

		$property->setId( $this->entityIdDeserializer->deserialize( $serialization['id'] ) );
	}

	private function setTermsFromSerialization( array $serialization, Property $property ) {
		if ( array_key_exists( 'labels', $serialization ) ) {
			$this->assertAttributeIsArray( $serialization, 'labels' );
			$property->getFingerprint()->setLabels(
				$this->termListDeserializer->deserialize( $serialization['labels'] )
			);
		}

		if ( array_key_exists( 'descriptions', $serialization ) ) {
			$this->assertAttributeIsArray( $serialization, 'descriptions' );
			$property->getFingerprint()->setDescriptions(
				$this->termListDeserializer->deserialize( $serialization['descriptions'] )
			);
		}

		if ( array_key_exists( 'aliases', $serialization ) ) {
			$this->assertAttributeIsArray( $serialization, 'aliases' );
			$property->getFingerprint()->setAliasGroups(
				$this->aliasGroupListDeserializer->deserialize( $serialization['aliases'] )
			);
		}
	}

	private function setStatementListFromSerialization( array $serialization, Property $property ) {
		if ( !array_key_exists( 'claims', $serialization ) ) {
			return;
		}

		$statements = $this->statementListDeserializer->deserialize( $serialization['claims'] );
		$property->setStatements( $statements );
	}

}
