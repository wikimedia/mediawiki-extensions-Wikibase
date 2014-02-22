<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\TypedObjectDeserializer;
use Wikibase\DataModel\Entity\Entity;

/**
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
abstract class EntityDeserializer extends TypedObjectDeserializer {

	/**
	 * @var Deserializer
	 */
	private $entityIdDeserializer;

	/**
	 * @var Deserializer
	 */
	private $claimsDeserializer;

	/**
	 * @param string $entityType
	 * @param Deserializer $entityIdDeserializer
	 * @param Deserializer $claimsDeserializer
	 */
	public function __construct( $entityType, Deserializer $entityIdDeserializer, Deserializer $claimsDeserializer ) {
		parent::__construct( $entityType, 'type' );

		$this->entityIdDeserializer = $entityIdDeserializer;
		$this->claimsDeserializer = $claimsDeserializer;
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

		return $this->getDeserialized( $serialization );
	}

	/**
	 * @param array $serialization
	 * @return Entity
	 */
	protected abstract function getPartiallyDeserialized( array $serialization );

	private function getDeserialized( array $serialization ) {
		$entity = $this->getPartiallyDeserialized( $serialization );

		$this->setIdFromSerialization( $serialization, $entity );
		$this->setLabelsFromSerialization( $serialization, $entity );
		$this->setDescriptionsFromSerialization( $serialization, $entity );
		$this->setAliasesFromSerialization( $serialization, $entity );
		$this->setClaimsFromSerialization( $serialization, $entity );

		return $entity;
	}

	private function setIdFromSerialization( array $serialization, Entity $entity ) {
		if ( !array_key_exists( 'id', $serialization ) ) {
			return;
		}

		$entity->setId( $this->entityIdDeserializer->deserialize( $serialization['id'] ) );
	}

	private function setLabelsFromSerialization( array $serialization, Entity $entity ) {
		if ( !array_key_exists( 'labels', $serialization ) ) {
			return;
		}
		$this->assertAttributeIsArray( $serialization, 'labels' );

		$entity->setLabels( $this->deserializeValuePerLanguageSerialization( $serialization['labels'] ) );
	}

	private function setDescriptionsFromSerialization( array $serialization, Entity $entity ) {
		if ( !array_key_exists( 'descriptions', $serialization ) ) {
			return;
		}
		$this->assertAttributeIsArray( $serialization, 'descriptions' );

		$entity->setDescriptions( $this->deserializeValuePerLanguageSerialization( $serialization['descriptions'] ) );
	}

	private function deserializeValuePerLanguageSerialization( array $serialization ) {
		$array = array();

		foreach( $serialization as $valueSerialization ) {
			$this->assertIsValidValueSerialization( $valueSerialization );
			$array[$valueSerialization['language']] = $valueSerialization['value'];
		}

		return $array;
	}

	private function setAliasesFromSerialization( array $serialization, Entity $entity ) {
		if ( !array_key_exists( 'aliases', $serialization ) ) {
			return;
		}
		$this->assertAttributeIsArray( $serialization, 'aliases' );

		foreach( $serialization['aliases'] as $aliasesPerLanguageSerialization ) {
			if ( !is_array( $aliasesPerLanguageSerialization ) ) {
				throw new DeserializationException( "Aliases attribute should be an array of array" );
			}

			foreach( $aliasesPerLanguageSerialization as $aliasSerialization ) {
				$this->assertIsValidValueSerialization( $aliasSerialization );
				$entity->addAliases( $aliasSerialization['language'], array( $aliasSerialization['value'] ) );
			}
		}
	}

	private function assertIsValidValueSerialization( $serialization ) {
		$this->requireAttribute( $serialization, 'language' );
		$this->requireAttribute( $serialization, 'value' );

		$this->assertAttributeInternalType( $serialization, 'language', 'string' );
		$this->assertAttributeInternalType( $serialization, 'value', 'string' );
	}

	private function setClaimsFromSerialization( array $serialization, Entity $entity ) {
		if ( !array_key_exists( 'claims', $serialization ) ) {
			return;
		}

		$entity->setClaims( $this->claimsDeserializer->deserialize( $serialization['claims'] ) );
	}
}
