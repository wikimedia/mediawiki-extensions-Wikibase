<?php

namespace Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\InvalidAttributeException;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Fingerprint;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class LegacyItemDeserializer implements Deserializer {

	private $idDeserializer;
	private $siteLinkListDeserializer;
	private $statementDeserializer;
	private $fingerprintDeserializer;

	/**
	 * @var Item
	 */
	private $item;
	private $serialization;

	public function __construct( Deserializer $idDeserializer, Deserializer $siteLinkListDeserializer,
		Deserializer $statementDeserializer, Deserializer $fingerprintDeserializer ) {

		$this->idDeserializer = $idDeserializer;
		$this->siteLinkListDeserializer = $siteLinkListDeserializer;
		$this->statementDeserializer = $statementDeserializer;
		$this->fingerprintDeserializer = $fingerprintDeserializer;
	}

	/**
	 * @param mixed $serialization
	 *
	 * @return Item
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'Item serialization should be an array' );
		}

		$this->serialization = $serialization;
		$this->item = Item::newEmpty();

		$this->setId();
		$this->addSiteLinks();
		$this->addStatements();
		$this->addFingerprint();

		return $this->item;
	}

	private function setId() {
		if ( array_key_exists( 'entity', $this->serialization ) ) {
			$this->item->setId( $this->idDeserializer->deserialize( $this->serialization['entity'] ) );
		}
	}

	private function addSiteLinks() {
		foreach ( $this->getSiteLinks() as $siteLink ) {
			$this->item->addSiteLink( $siteLink );
		}
	}

	private function getSiteLinks() {
		if ( array_key_exists( 'links', $this->serialization ) ) {
			return $this->siteLinkListDeserializer->deserialize( $this->serialization['links'] );
		}

		return array();
	}

	private function addStatements() {
		$this->normalizeLegacyClaimKeys();
		$this->item->setStatements( $this->buildStatementList() );
	}

	private function buildStatementList() {
		$statementList = new StatementList();

		foreach ( $this->getArrayFromKey( 'claims' ) as $claimSerialization ) {
			$this->assertClaimValueIsArray( $claimSerialization );
			$statementList->addStatement( $this->getStatement( $claimSerialization ) );
		}

		return $statementList;
	}

	private function getStatement( array $claimSerialization ) {
		$statementSerialization = $this->normalizeStatementSerialization( $claimSerialization );

		return $this->statementDeserializer->deserialize( $statementSerialization );
	}

	private function assertClaimValueIsArray( $value ) {
		if ( !is_array( $value ) ) {
			throw new DeserializationException( 'Claim serialization must be an array.' );
		}
	}

	private function normalizeLegacyClaimKeys() {
		// Compatibility with DataModel 0.2 and 0.3 ItemObjects.
		// (statements key got renamed to claims)
		if ( array_key_exists( 'statements', $this->serialization ) ) {
			$this->serialization['claims'] = $this->serialization['statements'];
			unset( $this->serialization['statements'] );
		}
	}

	private function normalizeStatementSerialization( $claimSerialization ) {
		$statementSerialization = $this->normalizeStatementRankKey( $claimSerialization );
		$statementSerialization = $this->normalizeReferencesKey( $statementSerialization );

		return $statementSerialization;
	}

	private function normalizeStatementRankKey( array $claimSerialization ) {
		if ( !isset( $claimSerialization['rank'] ) ) {
			$claimSerialization['rank'] = Claim::RANK_NORMAL;
		}

		return $claimSerialization;
	}

	private function normalizeReferencesKey( array $claimSerialization ) {
		if ( !isset( $claimSerialization['refs'] ) ) {
			$claimSerialization['refs'] = array();
		}

		return $claimSerialization;
	}

	private function getArrayFromKey( $key ) {
		if ( !array_key_exists( $key, $this->serialization ) ) {
			return array();
		}

		$this->assertKeyIsArray( $key );

		return $this->serialization[$key];
	}

	private function assertKeyIsArray( $key ) {
		if ( !is_array( $this->serialization[$key] ) ) {
			throw new InvalidAttributeException(
				$key,
				$this->serialization[$key],
				'The ' . $key . ' key should point to an array'
			);
		}
	}

	private function addFingerprint() {
		$this->item->setFingerprint( $this->getFingerprint() );
	}

	/**
	 * @return Fingerprint
	 */
	private function getFingerprint() {
		return $this->fingerprintDeserializer->deserialize( $this->serialization );
	}

}
