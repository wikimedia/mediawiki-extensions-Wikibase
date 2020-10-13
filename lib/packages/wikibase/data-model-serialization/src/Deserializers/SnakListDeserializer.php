<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * Package private
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 * @author Addshore
 */
class SnakListDeserializer implements Deserializer {

	/**
	 * @var Deserializer
	 */
	private $snakDeserializer;

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
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'The SnakList serialization should be an array' );
		}

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
				if ( !is_array( $snakArray ) ) {
					throw new DeserializationException(
						"The snaks per property \"$key\" should be an array"
					);
				}

				foreach ( $snakArray as $snakSerialization ) {
					/** @var Snak $snak */
					$snak = $this->snakDeserializer->deserialize( $snakSerialization );
					$snakList->addSnak( $snak );
				}
			} else {
				/** @var Snak $snak */
				$snak = $this->snakDeserializer->deserialize( $snakArray );
				$snakList->addSnak( $snak );
			}
		}

		return $snakList;
	}

}
