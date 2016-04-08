<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Snak\SnakList;

/**
 * Package private
 *
 * @license GPL-2.0+
 * @author Thomas Pellissier Tanon
 * @author Addshore
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
	 * @param array[] $serialization
	 *
	 * @throws DeserializationException
	 * @return SnakList
	 */
	public function deserialize( $serialization ) {
		$this->assertHasGoodFormat( $serialization );

		return $this->getDeserialized( $serialization );
	}

	/**
	 * @param array[] $serialization
	 *
	 * @return SnakList
	 */
	private function getDeserialized( array $serialization ) {
		$snakList = new SnakList();

		foreach ( $serialization as $key => $snakArray ) {
			if ( is_string( $key ) ) {
				foreach ( $snakArray as $snakSerialization ) {
					$snakList->addElement( $this->snakDeserializer->deserialize( $snakSerialization ) );
				}
			} else {
				$snakList->addElement( $this->snakDeserializer->deserialize( $snakArray ) );
			}
		}

		return $snakList;
	}

	/**
	 * @param array[] $serialization
	 *
	 * @throws DeserializationException
	 */
	private function assertHasGoodFormat( $serialization ) {
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'The SnakList serialization should be an array' );
		}

		foreach ( $serialization as $key => $snakArray ) {
			if ( is_string( $key ) && !is_array( $snakArray ) ) {
				throw new DeserializationException( 'The snaks per property should be an array' );
			}
		}
	}

}
