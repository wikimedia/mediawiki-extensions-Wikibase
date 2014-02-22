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
	 * @see Deserializer::isDeserializerFor
	 *
	 * @param mixed $serialization
	 *
	 * @return boolean
	 */
	public function isDeserializerFor( $serialization ) {
		return $this->isValidSerialization( $serialization );
	}

	private function isValidSerialization( $serialization ) {
		if( !is_array( $serialization ) ) {
			return false;
		}

		foreach( $serialization as $snaksArray ) {
			if( !$this->isValidSnakArraySerialization( $snaksArray ) ) {
				return false;
			}
		}

		return true;
	}

	private function isValidSnakArraySerialization( $serialization ) {
		if( !is_array( $serialization ) ) {
			return false;
		}

		foreach( $serialization as $snakSerialization ) {
			if( !$this->isValidSnakSerialization( $snakSerialization ) ) {
				return false;
			}
		}

		return true;
	}


	private function isValidSnakSerialization( $serialization ) {
		return $this->snakDeserializer->isDeserializerFor( $serialization );
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
		$snakList = new SnakList();

		foreach( $serialization as $snakArray ) {
			foreach( $snakArray as $snakSerialization ) {
				$snakList->addElement( $this->snakDeserializer->deserialize( $snakSerialization ) );
			}
		}

		return $snakList;
	}

	private function assertCanDeserialize( $serialization ) {
		if ( !$this->isValidSerialization( $serialization ) ) {
			throw new DeserializationException( 'The serialization is invalid!' );
		}
	}
}
