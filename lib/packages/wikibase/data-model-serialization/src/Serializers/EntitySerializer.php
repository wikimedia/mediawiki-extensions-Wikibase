<?php

namespace Wikibase\DataModel\Serializers;

use Serializers\Exceptions\SerializationException;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;

/**
 * @since 1.0
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
abstract class EntitySerializer implements Serializer {

	/**
	 * @var Serializer
	 */
	private $claimsSerializer;

	/**
	 * @param Serializer $claimsSerializer
	 */
	public function __construct( Serializer $claimsSerializer ) {
		$this->claimsSerializer = $claimsSerializer;
	}

	/**
	 * @see Serializer::isSerializerFor
	 *
	 * @param mixed $object
	 *
	 * @return boolean
	 */
	public function isSerializerFor( $object ) {
		return is_object( $object ) && $object instanceof Entity;
	}

	/**
	 * @see Serializer::serialize
	 *
	 * @param mixed $object
	 *
	 * @return array
	 * @throws SerializationException
	 */
	public function serialize( $object ) {
		if ( !$this->isSerializerFor( $object ) ) {
			throw new UnsupportedObjectException(
				$object,
				'EntitySerializer can only serialize Entity objects'
			);
		}

		return array_merge(
			$this->getSerialized( $object ),
			$this->getSpecificSerialization( $object )
		);
	}

	/**
	 * @param Entity $entity
	 *
	 * @return array
	 */
	protected abstract function getSpecificSerialization( Entity $entity );

	private function getSerialized( Entity $entity ) {
		$serialization = array(
			'type' => $entity->getType()
		);
		$this->addIdToSerialization( $entity, $serialization );
		$this->addClaimsToSerialization( $entity, $serialization );

		return $serialization;
	}

	private function addIdToSerialization( Entity $entity, array &$serialization ) {
		$id = $entity->getId();

		if ( $id === null ) {
			return;
		}

		$serialization['id'] = $id->getSerialization();
	}

	private function addClaimsToSerialization( Entity $entity, array &$serialization ) {
		$claims = new Claims( $entity->getClaims() );

		if ( $claims->isEmpty() ) {
			return;
		}

		$serialization['claims'] = $this->claimsSerializer->serialize( $claims );
	}
}
