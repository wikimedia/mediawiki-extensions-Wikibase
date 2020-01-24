<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;

/**
 * Package private
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class EntityIdDeserializer implements Deserializer {

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	public function __construct( EntityIdParser $entityIdParser ) {
		$this->entityIdParser = $entityIdParser;
	}

	/**
	 * @see Deserializer::deserialize
	 *
	 * @param string $serialization
	 *
	 * @throws DeserializationException
	 * @return EntityId
	 */
	public function deserialize( $serialization ) {
		if ( !is_string( $serialization ) ) {
			throw new DeserializationException( 'The serialization of an entity ID should be a string' );
		}

		try {
			return $this->entityIdParser->parse( $serialization );
		} catch ( EntityIdParsingException $e ) {
			throw new DeserializationException( "'$serialization' is not a valid entity ID", $e );
		}
	}

}
