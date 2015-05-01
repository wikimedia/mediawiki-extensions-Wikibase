<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\TypedObjectDeserializer;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Statement\StatementList;

/**
 * Package private
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
	private $fingerprintDeserializer;

	/**
	 * @var Deserializer
	 */
	private $claimsDeserializer;

	/**
	 * @param string $entityType
	 * @param Deserializer $entityIdDeserializer
	 * @param Deserializer $claimsDeserializer
	 */
	public function __construct(
		$entityType,
		Deserializer $entityIdDeserializer,
		Deserializer $fingerprintDeserializer,
		Deserializer $claimsDeserializer
	) {
		parent::__construct( $entityType, 'type' );

		$this->entityIdDeserializer = $entityIdDeserializer;
		$this->fingerprintDeserializer = $fingerprintDeserializer;
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

		$entity->setFingerprint( $this->fingerprintDeserializer->deserialize( $serialization ) );

		$this->setIdFromSerialization( $serialization, $entity );
		$this->setClaimsFromSerialization( $serialization, $entity );

		return $entity;
	}

	private function setIdFromSerialization( array $serialization, Entity $entity ) {
		if ( !array_key_exists( 'id', $serialization ) ) {
			return;
		}

		$entity->setId( $this->entityIdDeserializer->deserialize( $serialization['id'] ) );
	}

	private function setClaimsFromSerialization( array $serialization, Entity $entity ) {
		if ( !array_key_exists( 'claims', $serialization ) || !method_exists( $entity, 'setStatements' ) ) {
			return;
		}

		$claims = $this->claimsDeserializer->deserialize( $serialization['claims'] );
		$statements = new StatementList( iterator_to_array( $claims ) );
		$entity->setStatements( $statements );
	}

}
