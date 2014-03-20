<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\ReferenceList;

/**
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class ReferenceListDeserializer implements Deserializer {

	/**
	 * @var Deserializer
	 */
	private $referenceDeserializer;

	/**
	 * @param Deserializer $referenceDeserializer
	 */
	public function __construct( Deserializer $referenceDeserializer ) {
		$this->referenceDeserializer = $referenceDeserializer;
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
		$this->assertIsArray( $serialization );

		return $this->getDeserialized( $serialization );
	}

	private function getDeserialized( array $serialization ) {
		$referenceList = new ReferenceList();

		foreach( $serialization as $referenceSerialization ) {
			$referenceList->addReference( $this->referenceDeserializer->deserialize( $referenceSerialization ) );
		}

		return $referenceList;
	}

	private function assertIsArray( $serialization ) {
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'The ReferenceList serialization should be an array!' );
		}
	}
}
