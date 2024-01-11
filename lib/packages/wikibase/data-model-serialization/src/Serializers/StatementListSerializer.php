<?php

namespace Wikibase\DataModel\Serializers;

use Serializers\DispatchableSerializer;
use Serializers\Exceptions\SerializationException;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\DataModel\Statement\StatementList;

/**
 * Package private
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class StatementListSerializer implements DispatchableSerializer {

	/**
	 * @var Serializer
	 */
	private $statementSerializer;

	/**
	 * @param Serializer $statementSerializer
	 */
	public function __construct( Serializer $statementSerializer ) {
		$this->statementSerializer = $statementSerializer;
	}

	/**
	 * @see Serializer::isSerializerFor
	 *
	 * @param mixed $object
	 *
	 * @return bool
	 */
	public function isSerializerFor( $object ) {
		return $object instanceof StatementList;
	}

	/**
	 * @see Serializer::serialize
	 *
	 * @param StatementList $object
	 *
	 * @throws SerializationException
	 * @return array[]
	 */
	public function serialize( $object ) {
		if ( !$this->isSerializerFor( $object ) ) {
			throw new UnsupportedObjectException(
				$object,
				'StatementListSerializer can only serialize StatementList objects'
			);
		}

		return $this->getSerialized( $object );
	}

	private function getSerialized( StatementList $statementList ) {
		$serialization = [];

		foreach ( $statementList->toArray() as $statement ) {
			$idSerialization = $statement->getPropertyId()->getSerialization();

			if ( !array_key_exists( $idSerialization, $serialization ) ) {
				$serialization[$idSerialization] = [];
			}

			$serialization[$idSerialization][] = $this->statementSerializer->serialize( $statement );
		}

		return $serialization;
	}

}
