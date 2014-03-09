<?php

namespace Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Internal\LegacyIdInterpreter;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemDeserializer implements Deserializer {

	private $idDeserializer;
	private $siteLinkListDeserializer;
	private $claimDeserializer;

	/**
	 * @var Item
	 */
	private $item;
	private $serialization;

	public function __construct( Deserializer $idDeserializer, Deserializer $siteLinkListDeserializer,
		Deserializer $claimDeserializer ) {

		$this->idDeserializer = $idDeserializer;
		$this->siteLinkListDeserializer = $siteLinkListDeserializer;
		$this->claimDeserializer = $claimDeserializer;
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
		foreach ( $this->getClaimsSerialization() as $claimSerialization ) {
			$this->item->addClaim( $this->claimDeserializer->deserialize( $claimSerialization ) );
		}
	}

	private function getClaimsSerialization() {
		if ( !array_key_exists( 'claims', $this->serialization ) ) {
			return array();
		}

		if ( !is_array( $this->serialization['claims'] ) ) {
			throw new DeserializationException( 'The claims key should point to an array' );
		}

		return $this->serialization['claims'];
	}

}