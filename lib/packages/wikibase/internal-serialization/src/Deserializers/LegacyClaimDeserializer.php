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
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LegacyClaimDeserializer implements Deserializer {

	private $snakDeserializer;
	private $snakListDeserializer;

	private $serialization;

	public function __construct( Deserializer $snakDeserializer, Deserializer $snakListDeserializer ) {
		$this->snakDeserializer = $snakDeserializer;
		$this->snakListDeserializer = $snakListDeserializer;
	}

	/**
	 * @param mixed $serialization
	 *
	 * @return Claim
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		$this->serialization = $serialization;

		$this->assertIsArray();
		$this->assertHasKey( 'm', 'Mainsnak serialization is missing' );
		$this->assertHasKey( 'q', 'Qualifiers serialization is missing' );
		$this->assertHasKey( 'g', 'Guid is missing in Claim serialization' );

		return $this->newClaimFormSerialization();
	}

	private function assertIsArray() {
		if ( !is_array( $this->serialization ) ) {
			throw new DeserializationException( 'Claim serialization should be an array' );
		}
	}

	private function assertHasKey( $key, $message ) {
		if ( !array_key_exists( $key, $this->serialization ) ) {
			throw new MissingAttributeException( $key, $message );
		}
	}

	private function newClaimFormSerialization() {
		$claim = $this->newClaimOrStatement();

		$this->setGuid( $claim );

		return $claim;
	}

	private function newClaimOrStatement() {
		if ( $this->isStatement() ) {
			return $this->getStatement();
		}
		else {
			return $this->getClaim();
		}
	}

	private function isStatement() {
		return array_key_exists( 'rank', $this->serialization );
	}

	private function getClaim() {
		return new Claim(
			$this->getMainSnak(),
			$this->getQualifiers()
		);
	}

	private function getStatement() {
		$statement = new Statement(
			$this->getMainSnak(),
			$this->getQualifiers(),
			$this->getReferences()
		);

		$this->setRank( $statement );

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

	private function getMainSnak() {
		return $this->snakDeserializer->deserialize( $this->serialization['m'] );
	}

	private function getQualifiers() {
		return $this->snakListDeserializer->deserialize( $this->serialization['q'] );
	}

	private function setGuid( Claim $claim ) {
		try {
			$claim->setGuid( $this->serialization['g'] );
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
