<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\ReferenceList;

/**
 * Package private
 *
 * @license GPL-2.0+
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
	 * @param array[] $serialization
	 *
	 * @throws DeserializationException
	 * @return ReferenceList
	 */
	public function deserialize( $serialization ) {
		$this->assertIsArray( $serialization );

		return $this->getDeserialized( $serialization );
	}

	/**
	 * @param array[] $serialization
	 *
	 * @return ReferenceList
	 */
	private function getDeserialized( array $serialization ) {
		$referenceList = new ReferenceList();

		foreach ( $serialization as $referenceSerialization ) {
			$referenceList->addReference( $this->referenceDeserializer->deserialize( $referenceSerialization ) );
		}

		return $referenceList;
	}

	private function assertIsArray( $serialization ) {
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'The ReferenceList serialization should be an array' );
		}
	}

}
