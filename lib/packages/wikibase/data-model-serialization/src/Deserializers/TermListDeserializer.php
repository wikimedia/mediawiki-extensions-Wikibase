<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Term\TermList;

/**
 * Package private
 *
 * @author Adam Shorland
 */
class TermListDeserializer implements Deserializer {

	/**
	 * @param Deserializer $termDeserializer
	 */
	public function __construct( Deserializer $termDeserializer ) {
		$this->termDeserializer = $termDeserializer;
	}

	/**
	 * @param mixed $serialization
	 *
	 * @return TermList
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		$this->assertCanDeserialize( $serialization );

		return $this->getDeserialized( $serialization );
	}

	/**
	 * @param array $serialization
	 *
	 * @return TermList
	 */
	private function getDeserialized( $serialization ) {
		$termList = new TermList();
		foreach ( $serialization as $termSerialization ) {
			$termList->setTerm( $this->termDeserializer->deserialize( $termSerialization ) );
		}
		return $termList;
	}

	/**
	 * @param array $serialization
	 */
	private function assertCanDeserialize( $serialization ) {
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'The term list serialization should be an array' );
		}
	}

}
