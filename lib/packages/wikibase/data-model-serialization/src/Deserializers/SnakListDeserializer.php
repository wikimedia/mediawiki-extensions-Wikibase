<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Snak\SnakList;

/**
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class SnakListDeserializer implements Deserializer {

	/**
	 * @var Deserializer
	 */
	private $snakDeserializer;

	/**
	 * @param Deserializer $snakDeserializer
	 */
	public function __construct( Deserializer $snakDeserializer ) {
		$this->snakDeserializer = $snakDeserializer;
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
		$snakList = new SnakList();

		foreach( $serialization as $snakArray ) {
			foreach( $snakArray as $snakSerialization ) {
				$snakList->addElement( $this->snakDeserializer->deserialize( $snakSerialization ) );
			}
		}

		return $snakList;
	}

	private function assertHasGoodFormat( $serialization ) {
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'The SnakList serialization should be an array' );
		}

		foreach ( $serialization as $snaksOfPropertySerialization ) {
			if ( !is_array( $snaksOfPropertySerialization ) ) {
				throw new DeserializationException( 'The snaks per property should be an array' );
			}
		}
	}
}
