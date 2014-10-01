<?php

namespace Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\MissingAttributeException;
use InvalidArgumentException;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Statement\Statement;

/**
 * @licence GNU GPL v2+
 * @author Katie Filbert < @aude.wiki@gmail.com >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LegacyStatementDeserializer implements Deserializer {

	private $claimDeserializer;
	private $snakListDeserializer;

	private $serialization;

	public function __construct(
		Deserializer $claimDeserializer,
		Deserializer $snakListDeserializer
	) {
		$this->claimDeserializer = $claimDeserializer;
		$this->snakListDeserializer = $snakListDeserializer;
	}

	/**
	 * @param mixed $serialization
	 *
	 * @return Statement
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		$this->serialization = $serialization;

		$this->assertIsArray();
		$this->assertHasKey( 'm', 'Mainsnak serialization is missing' );
		$this->assertHasKey( 'q', 'Qualifiers serialization is missing' );
		$this->assertHasKey( 'g', 'Guid is missing in serialization' );
		$this->assertHasKey( 'rank', 'Rank is missing in serialization' );
		$this->assertHasKey( 'refs', 'Refs are missing in serialization' );

		return $this->newStatement();
	}

	private function assertIsArray() {
		if ( !is_array( $this->serialization ) ) {
			throw new DeserializationException( 'Statement serialization should be an array' );
		}
	}

	private function assertHasKey( $key, $message ) {
		if ( !array_key_exists( $key, $this->serialization ) ) {
			throw new MissingAttributeException( $key, $message );
		}
	}

	private function newStatement() {
		$claim = $this->claimDeserializer->deserialize( $this->serialization );

		$statement = $this->newStatementFromClaim( $claim );
		$statement->setReferences( $this->getReferences() );
		$this->setRank( $statement );

		return $statement;
	}

	private function newStatementFromClaim( Claim $claim ) {
		$statement = new Statement(
			$claim->getMainSnak(),
			$claim->getQualifiers()
		);

		$statement->setGuid( $claim->getGuid() );

		return $statement;
	}

	private function setRank( Statement $statement ) {
		try {
			$statement->setRank( $this->serialization['rank'] );
		}
		catch ( InvalidArgumentException $ex ) {
			throw new DeserializationException( $ex->getMessage(), $ex );
		}
	}

	private function getReferences() {
		$references = array();

		foreach ( $this->serialization['refs'] as $referenceSerialization ) {
			$references[] = $this->deserializeReference( $referenceSerialization );
		}

		return new ReferenceList( $references );
	}

	private function deserializeReference( $serialization ) {
		return new Reference( $this->snakListDeserializer->deserialize( $serialization ) );
	}

}
