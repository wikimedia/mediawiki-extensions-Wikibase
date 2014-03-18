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
	 * @param Serializer $claimSerializer
	 */
	public function __construct( Serializer $claimSerializer ) {
		$this->claimSerializer = $claimSerializer;
	}

	/**
	 * @see Serializer::isSerializerFor
	 *
	 * @param mixed $object
	 *
	 * @return boolean
	 */
	public function isSerializerFor( $object ) {
		return is_object( $object ) && $object instanceof Claims;
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

		return $serialization;
	}
}
