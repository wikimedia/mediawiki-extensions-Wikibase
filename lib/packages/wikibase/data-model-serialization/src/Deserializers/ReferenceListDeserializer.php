<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;

/**
 * Package private
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class ReferenceListDeserializer implements Deserializer {

	/**
	 * @var Deserializer
	 */
	private $referenceDeserializer;

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
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'The ReferenceList serialization should be an array' );
		}

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
			/** @var Reference $reference */
			$reference = $this->referenceDeserializer->deserialize( $referenceSerialization );
			$referenceList->addReference( $reference );
		}

		return $referenceList;
	}

}
