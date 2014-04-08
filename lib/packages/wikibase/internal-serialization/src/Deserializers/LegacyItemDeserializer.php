<?php

namespace Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\InvalidAttributeException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LegacyItemDeserializer implements Deserializer {

	private $idDeserializer;
	private $siteLinkListDeserializer;
	private $claimDeserializer;
	private $termsDeserializer;

	/**
	 * @var Item
	 */
	private $item;
	private $serialization;

	public function __construct( Deserializer $idDeserializer, Deserializer $siteLinkListDeserializer,
		Deserializer $claimDeserializer, Deserializer $termsDeserializer ) {

		$this->idDeserializer = $idDeserializer;
		$this->siteLinkListDeserializer = $siteLinkListDeserializer;
		$this->claimDeserializer = $claimDeserializer;
		$this->termsDeserializer = $termsDeserializer;
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
		$this->addClaims();
		$this->addTerms();

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

	private function addClaims() {
		$this->normalizeLegacyClaimKey();

		foreach ( $this->getArrayFromKey( 'claims' ) as $claimSerialization ) {
			$this->item->addClaim( $this->claimDeserializer->deserialize( $claimSerialization ) );
		}
	}

	private function normalizeLegacyClaimKey() {
		// Compatibility with DataModel 0.2 and 0.3 ItemObjects.
		// (statements key got renamed to claims)
		if ( array_key_exists( 'statements', $this->serialization ) ) {
			$this->serialization['claims'] = $this->serialization['statements'];
			unset( $this->serialization['statements'] );
		}
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

	private function addTerms() {
		$terms = $this->getFingerprint();

		// TODO: try catch once setters do validation
		$this->item->setLabels( $terms->getLabels()->toTextArray() );
		$this->item->setDescriptions( $terms->getDescriptions()->toTextArray() );
		$this->setAliases( $terms->getAliases() );
	}

	/**
	 * @return Fingerprint
	 */
	private function getFingerprint() {
		return $this->termsDeserializer->deserialize( $this->serialization );
	}

	private function setAliases( AliasGroupList $aliases ) {
		/**
		 * @var AliasGroup $aliasGroup
		 */
		foreach ( $aliases as $aliasGroup ) {
			$this->item->setAliases( $aliasGroup->getLanguageCode(), $aliasGroup->getAliases() );
		}
	}

}