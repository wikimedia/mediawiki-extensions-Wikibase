<?php

namespace Wikibase\DataModel\Serializers;

use Serializers\DispatchableSerializer;
use Serializers\Exceptions\SerializationException;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Property;

/**
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 * @author Jan Zerebecki < jan.wikimedia@zerebecki.de >
 */
class PropertySerializer implements DispatchableSerializer {

	/**
	 * @var FingerprintSerializer
	 */
	private $fingerprintSerializer;

	/**
	 * @var Serializer
	 */
	private $claimsSerializer;

	/**
	 * @param Serializer $claimsSerializer
	 */
	public function __construct( FingerprintSerializer $fingerprintSerializer, Serializer $claimsSerializer ) {
		$this->fingerprintSerializer = $fingerprintSerializer;
		$this->claimsSerializer = $claimsSerializer;
	}

	/**
	 * @see Serializer::isSerializerFor
	 *
	 * @param mixed $object
	 *
	 * @return bool
	 */
	public function isSerializerFor( $object ) {
		return $object instanceof Property;
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
				'PropertySerializer can only serialize Property objects.'
			);
		}

		return $this->getSerialized( $object );
	}

	private function getSerialized( Property $entity ) {
		$serialization = array(
			'type' => $entity->getType(),
			'datatype' => $entity->getDataTypeId(),
		);

		$this->fingerprintSerializer->addBasicsToSerialization( $entity, $serialization );
		$this->addClaimsToSerialization( $entity, $serialization );

		return $serialization;
	}

	private function addClaimsToSerialization( Entity $entity, array &$serialization ) {
		$claims = new Claims( $entity->getStatements() );

		$serialization['claims'] = $this->claimsSerializer->serialize( $claims );
	}

}
