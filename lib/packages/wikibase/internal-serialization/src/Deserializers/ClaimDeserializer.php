<?php

namespace Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\MissingAttributeException;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Snak\SnakList;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ClaimDeserializer implements Deserializer {

	private $snakDeserializer;
	private $qualifiersDeserializer;

	private $serialization;

	public function __construct( Deserializer $snakDeserializer, Deserializer $qualifiersDeserializer ) {
		$this->snakDeserializer = $snakDeserializer;
		$this->qualifiersDeserializer = $qualifiersDeserializer;
	}

	/**
	 * @param mixed $serialization
	 *
	 * @return SnakList
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		$this->serialization = $serialization;

		$this->assertIsArray();
		$this->assertHasKey( 'm', 'Mainsnak serialization is missing' );
		$this->assertHasKey( 'q', 'Qualifiers serialization is missing' );
		$this->assertHasKey( 'g', 'Guid is missing in Claim serialization' );

		$claim = new Claim(
			$this->snakDeserializer->deserialize( $serialization['m'] ),
			$this->qualifiersDeserializer->deserialize( $serialization['q'] )
		);

		$claim->setGuid( $serialization['g'] );

		return $claim;
	}

	private function assertIsArray() {
		if ( !is_array( $this->serialization ) ) {
			throw new DeserializationException( 'Claim serialization should be an array' );
		}
	}

	private function assertHasKey( $key, $message ) {
		if ( !array_key_exists( $key, $this->serialization ) ) {
			throw new MissingAttributeException( $key, $message );
		}
	}

}