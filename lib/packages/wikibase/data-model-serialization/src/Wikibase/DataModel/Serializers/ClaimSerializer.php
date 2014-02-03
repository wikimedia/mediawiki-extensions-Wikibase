<?php

namespace Wikibase\DataModel\Serializers;

use Serializers\Exceptions\SerializationException;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\Statement;

/**
 * @since 1.0
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class ClaimSerializer implements Serializer {

	private $rankLabels = array(
		Statement::RANK_DEPRECATED => 'depreciated',
		Statement::RANK_NORMAL => 'normal',
		Statement::RANK_PREFERRED => 'preferred'
	);

	/**
	 * @var Serializer
	 */
	private $snakSerializer;

	/**
	 * @param Serializer $snakSerializer
	 */
	public function __construct( Serializer $snakSerializer ) {
		$this->snakSerializer = $snakSerializer;
	}

	/**
	 * @see Serializer::isSerializerFor
	 *
	 * @param mixed $object
	 *
	 * @return boolean
	 */
	public function isSerializerFor( $object ) {
		return is_object( $object ) && $object instanceof Claim;
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
				'ClaimSerializer can only serialize Claim objects'
			);
		}

		return $this->getSerialized( $object );
	}

	private function getSerialized( Claim $claim ) {
		$serialization = array(
			'mainsnak' => $this->snakSerializer->serialize( $claim->getMainSnak() ),
			'type' => $claim instanceof Statement ? 'statement' : 'claim'
		);
		$this->addGuidToSerialization( $claim, $serialization );
		$this->addRankToSerialization( $claim, $serialization );

		return $serialization;
	}

	private function addGuidToSerialization( Claim $claim, array &$serialization ) {
		$guid = $claim->getGuid();
		if ( $guid !== null ) {
			$serialization['id'] = $guid;
		}
	}

	private function addRankToSerialization( Claim $claim, array &$serialization ) {
		if ( $claim instanceof Statement ) {
			$serialization['rank'] = $this->rankLabels[$claim->getRank()];
		}
	}
}
