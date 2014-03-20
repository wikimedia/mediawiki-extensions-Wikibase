<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Claim\Claims;

/**
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class ClaimsDeserializer implements Deserializer {

	/**
	 * @var Deserializer
	 */
	private $claimDeserializer;

	/**
	 * @param Deserializer $claimDeserializer
	 */
	public function __construct( Deserializer $claimDeserializer ) {
		$this->claimDeserializer = $claimDeserializer;
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
		$this->assertHasGoodFormat( $serialization );

		return $this->getDeserialized( $serialization );
	}

	private function getDeserialized( array $serialization ) {
		$claimList = new Claims();

		foreach( $serialization as $claimArray ) {
			foreach( $claimArray as $claimSerialization ) {
				$claimList->addClaim( $this->claimDeserializer->deserialize( $claimSerialization ) );
			}
		}

		return $claimList;
	}

	private function assertHasGoodFormat( $serialization ) {
		if( !is_array( $serialization ) ) {
			throw new DeserializationException( 'The Claims serialization should be an array' );
		}

		foreach( $serialization as $claimsArray ) {
			if( !is_array( $claimsArray ) ) {
				throw new DeserializationException( 'The claims per property should be an array' );
			}
		}
	}
}
