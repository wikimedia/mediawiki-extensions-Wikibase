<?php

namespace Wikibase\DataModel\Serializers;

use Serializers\DispatchableSerializer;
use Serializers\Exceptions\SerializationException;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\DataModel\Entity\Property;

/**
 * Package private
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
	private $statementListSerializer;

	/**
	 * @param FingerprintSerializer $fingerprintSerializer
	 * @param Serializer $statementListSerializer
	 */
	public function __construct( FingerprintSerializer $fingerprintSerializer, Serializer $statementListSerializer ) {
		$this->fingerprintSerializer = $fingerprintSerializer;
		$this->statementListSerializer = $statementListSerializer;
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
	 * @param Property $object
	 *
	 * @throws SerializationException
	 * @return array
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

	private function getSerialized( Property $property ) {
		$serialization = array(
			'type' => $property->getType(),
			'datatype' => $property->getDataTypeId(),
		);

		$this->fingerprintSerializer->addBasicsToSerialization(
			$property->getId(),
			$property->getFingerprint(),
			$serialization
		);

		$this->addStatementListToSerialization( $property, $serialization );

		return $serialization;
	}

	private function addStatementListToSerialization( Property $property, array &$serialization ) {
		$serialization['claims'] = $this->statementListSerializer->serialize( $property->getStatements() );
	}

}
