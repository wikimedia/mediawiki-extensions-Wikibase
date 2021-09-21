<?php

namespace Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Snak\SnakList;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LegacySnakListDeserializer implements Deserializer {

	/**
	 * @var Deserializer
	 */
	private $snakDeserializer;

	public function __construct( Deserializer $snakDeserializer ) {
		$this->snakDeserializer = $snakDeserializer;
	}

	/**
	 * @param array $serialization
	 *
	 * @return SnakList
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'SnakList serialization should be an array' );
		}

		$snaks = [];

		foreach ( $serialization as $snakSerialization ) {
			$snaks[] = $this->snakDeserializer->deserialize( $snakSerialization );
		}

		return new SnakList( $snaks );
	}

}
