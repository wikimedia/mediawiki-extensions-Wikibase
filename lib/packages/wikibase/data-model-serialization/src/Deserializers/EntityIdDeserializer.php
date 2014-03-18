<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;

/**
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class EntityIdDeserializer implements Deserializer {

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @param EntityIdParser $entityIdParser
	 */
	public function __construct( EntityIdParser $entityIdParser ) {
		$this->entityIdParser = $entityIdParser;
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
		$this->assertEntityIdIsString( $serialization );

		try {
			return $this->entityIdParser->parse( $serialization );
		} catch ( EntityIdParsingException $e ) {
			throw new DeserializationException( "'$serialization' is not a valid entity ID", $e );
		}
	}

	private function assertEntityIdIsString( $serialization ) {
		if ( !is_string( $serialization ) ) {
			throw new DeserializationException( 'The serialization of an entity ID should be a string' );
		}
	}
}
