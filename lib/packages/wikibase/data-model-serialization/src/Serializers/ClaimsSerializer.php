<?php

namespace Wikibase\DataModel\Serializers;

use Serializers\DispatchableSerializer;
use Serializers\Exceptions\SerializationException;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Statement\Statement;

/**
 * Package private
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class ClaimsSerializer implements DispatchableSerializer {

	/**
	 * @var Serializer
	 */
	private $statementSerializer;

	/**
	 * @var bool
	 */
	private $useObjectsForMaps;

	/**
	 * @param Serializer $statementSerializer
	 * @param bool $useObjectsForMaps
	 */
	public function __construct( Serializer $statementSerializer, $useObjectsForMaps ) {
		$this->statementSerializer = $statementSerializer;
		$this->useObjectsForMaps = $useObjectsForMaps;
	}

	/**
	 * @see Serializer::isSerializerFor
	 *
	 * @param mixed $object
	 *
	 * @return bool
	 */
	public function isSerializerFor( $object ) {
		return $object instanceof Claims;
	}

	/**
	 * @see Serializer::serialize
	 *
	 * @param Claims $object
	 *
	 * @throws SerializationException
	 * @return array
	 */
	public function serialize( $object ) {
		if ( !$this->isSerializerFor( $object ) ) {
			throw new UnsupportedObjectException(
				$object,
				'ClaimsSerializer can only serialize Claims objects'
			);
		}

		return $this->getSerialized( $object );
	}

	private function getSerialized( Claims $statements ) {
		$serialization = array();

		/** @var Statement $statement */
		foreach ( $statements as $statement ) {
			$serialization[$statement->getMainSnak()->getPropertyId()->getSerialization()][] = $this->statementSerializer->serialize( $statement );
		}

		if ( $this->useObjectsForMaps ) {
			$serialization = (object)$serialization;
		}
		return $serialization;
	}

}
