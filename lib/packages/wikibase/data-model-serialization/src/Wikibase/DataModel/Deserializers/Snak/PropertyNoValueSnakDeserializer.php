<?php

namespace Wikibase\DataModel\Deserializers\Snak;

use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\InvalidAttributeException;
use Deserializers\TypedObjectDeserializer;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;

/**
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class PropertyNoValueSnakDeserializer extends TypedObjectDeserializer {

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @param EntityIdParser $entityIdParser
	 */
	public function __construct( EntityIdParser $entityIdParser ) {
		parent::__construct( 'novalue', 'snaktype' );

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
		$this->assertCanDeserialize( $serialization );
		$this->requireAttributes( $serialization, 'property' );

		return $this->getDeserialized( $serialization );
	}

	private function getDeserialized( array $serialization ) {
		return new PropertyNoValueSnak(
			$this->parsePropertyId( $serialization['property'] )
		);
	}

	private function parsePropertyId( $propertyId ) {
		try {
			return $this->entityIdParser->parse( $propertyId );
		} catch ( EntityIdParsingException $e ) {
			throw new InvalidAttributeException(
				'property',
				$propertyId,
				"'$propertyId' is not a valid property ID"
			);
		}
	}
}
