<?php

namespace Wikibase\DataModel\Serializers;

use Serializers\DispatchableSerializer;
use Serializers\Exceptions\SerializationException;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;

/**
 * Package private
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class StatementSerializer implements DispatchableSerializer {

	private $rankLabels = array(
		Statement::RANK_DEPRECATED => 'deprecated',
		Statement::RANK_NORMAL => 'normal',
		Statement::RANK_PREFERRED => 'preferred'
	);

	/**
	 * @var Serializer
	 */
	private $snakSerializer;

	/**
	 * @var Serializer
	 */
	private $snaksSerializer;

	/**
	 * @var Serializer
	 */
	private $referencesSerializer;

	/**
	 * @param Serializer $snakSerializer
	 * @param Serializer $snaksSerializer
	 * @param Serializer $referencesSerializer
	 */
	public function __construct( Serializer $snakSerializer, Serializer $snaksSerializer, Serializer $referencesSerializer ) {
		$this->snakSerializer = $snakSerializer;
		$this->snaksSerializer = $snaksSerializer;
		$this->referencesSerializer = $referencesSerializer;
	}

	/**
	 * @see Serializer::isSerializerFor
	 *
	 * @param mixed $object
	 *
	 * @return bool
	 */
	public function isSerializerFor( $object ) {
		return $object instanceof Statement;
	}

	/**
	 * @see Serializer::serialize
	 *
	 * @param Statement $object
	 *
	 * @return array
	 * @throws SerializationException
	 */
	public function serialize( $object ) {
		if ( !$this->isSerializerFor( $object ) ) {
			throw new UnsupportedObjectException(
				$object,
				'StatementSerializer can only serialize Statement objects'
			);
		}

		return $this->getSerialized( $object );
	}

	private function getSerialized( Statement $statement ) {
		$serialization = array(
			'mainsnak' => $this->snakSerializer->serialize( $statement->getMainSnak() ),
			'type' => 'statement'
		);

		$this->addQualifiersToSerialization( $statement, $serialization );
		$this->addGuidToSerialization( $statement, $serialization );
		$this->addRankToSerialization( $statement, $serialization );
		$this->addReferencesToSerialization( $statement, $serialization );

		return $serialization;
	}

	private function addGuidToSerialization( Statement $statement, array &$serialization ) {
		$guid = $statement->getGuid();
		if ( $guid !== null ) {
			$serialization['id'] = $guid;
		}
	}

	private function addRankToSerialization( Statement $statement, array &$serialization ) {
		$serialization['rank'] = $this->rankLabels[$statement->getRank()];
	}

	private function addReferencesToSerialization( Statement $statement, array &$serialization ) {
		$references = $statement->getReferences();

		if ( $references->count() != 0 ) {
			$serialization['references'] = $this->referencesSerializer->serialize( $statement->getReferences() );
		}
	}

	private function addQualifiersToSerialization( Statement $statement, &$serialization ) {
		$qualifiers = $statement->getQualifiers();

		if ( $qualifiers->count() !== 0 ) {
			$serialization['qualifiers'] = $this->snaksSerializer->serialize( $qualifiers );
			$serialization['qualifiers-order'] = $this->buildQualifiersOrderList( $qualifiers );
		}
	}

	private function buildQualifiersOrderList( SnakList $snaks ) {
		$list = array();

		/**
		 * @var Snak $snak
		 */
		foreach ( $snaks as $snak ) {
			$id = $snak->getPropertyId()->getSerialization();
			if ( !in_array( $id, $list ) ) {
				$list[] = $id;
			}
		}

		return $list;
	}

}
