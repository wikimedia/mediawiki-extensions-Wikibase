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

	private function isValidSerialization( $serialization ) {
		if( !is_array( $serialization ) ) {
			return false;
		}

		foreach( $serialization as $claimsArray ) {
			if( !$this->isValidClaimArraySerialization( $claimsArray ) ) {
				return false;
			}
		}

		return true;
	}

	private function isValidClaimArraySerialization( $serialization ) {
		return is_array( $serialization );
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
		$this->assertCanDeserialize( $serialization );

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

	private function assertCanDeserialize( $serialization ) {
		if ( !$this->isValidSerialization( $serialization ) ) {
			throw new DeserializationException( 'The serialization is invalid!' );
		}
	}
}
