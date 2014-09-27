<?php

namespace Wikibase\DataModel\Serializers;

use Serializers\DispatchableSerializer;
use Serializers\Exceptions\SerializationException;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Claims;

/**
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class ClaimsSerializer implements DispatchableSerializer {

	/**
	 * @var Serializer
	 */
	protected $claimSerializer;

	/**
	 * @var bool
	 */
	protected $useObjectsForMaps;

	/**
	 * @param Serializer $claimSerializer
	 * @param bool $useObjectsForMaps
	 */
	public function __construct( Serializer $claimSerializer, $useObjectsForMaps ) {
		$this->claimSerializer = $claimSerializer;
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
	 * @param mixed $object
	 *
	 * @return array
	 * @throws SerializationException
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

	private function getSerialized( Claims $claims ) {
		$serialization = array();

		/**
		 * @var Claim $claim
		 */
		foreach( $claims as $claim ) {
			$serialization[$claim->getMainSnak()->getPropertyId()->getPrefixedId()][] = $this->claimSerializer->serialize( $claim );
		}

		if ( $this->useObjectsForMaps ) {
			$serialization = (object)$serialization;
		}
		return $serialization;
	}

}
