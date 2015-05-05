<?php

namespace Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\MissingAttributeException;
use InvalidArgumentException;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Statement\Statement;

/**
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class LegacyStatementDeserializer implements Deserializer {

	/**
	 * @var Deserializer
	 */
	private $snakDeserializer;

	/**
	 * @var Deserializer
	 */
	private $snakListDeserializer;

	public function __construct( Deserializer $snakDeserializer, Deserializer $snakListDeserializer ) {
		$this->snakDeserializer = $snakDeserializer;
		$this->snakListDeserializer = $snakListDeserializer;
	}

	/**
	 * @param array $serialization
	 *
	 * @return Statement
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'Statement serialization must be an array' );
		}

		$this->assertHasKey( $serialization, 'm', 'Mainsnak serialization is missing' );
		$this->assertHasKey( $serialization, 'q', 'Qualifiers serialization is missing' );
		$this->assertHasKey( $serialization, 'g', 'Guid is missing in serialization' );
		$this->assertHasKey( $serialization, 'rank', 'Rank is missing in serialization' );
		$this->assertHasKey( $serialization, 'refs', 'Refs are missing in serialization' );

		return $this->newStatement( $serialization );
	}

	private function assertHasKey( array $serialization, $key, $message ) {
		if ( !array_key_exists( $key, $serialization ) ) {
			throw new MissingAttributeException( $key, $message );
		}
	}

	private function newStatement( array $serialization ) {
		$statement = new Statement(
			$this->snakDeserializer->deserialize( $serialization['m'] ),
			$this->snakListDeserializer->deserialize( $serialization['q'] ),
			$this->getReferences( $serialization['refs'] )
		);

		try {
			$statement->setRank( $serialization['rank'] );
			$statement->setGuid( $serialization['g'] );
		} catch ( InvalidArgumentException $ex ) {
			throw new DeserializationException( $ex->getMessage(), $ex );
		}

		return $statement;
	}

	private function getReferences( array $refs ) {
		$references = array();

		foreach ( $refs as $serialization ) {
			$references[] = new Reference( $this->snakListDeserializer->deserialize( $serialization ) );
		}

		return new ReferenceList( $references );
	}

}
