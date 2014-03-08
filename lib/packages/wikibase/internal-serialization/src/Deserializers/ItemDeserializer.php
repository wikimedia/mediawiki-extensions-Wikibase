<?php

namespace Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Snak\Snak;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemDeserializer implements Deserializer {

	private $siteLinkListDeserializer;

	/**
	 * @var Item
	 */
	private $item;
	private $serialization;

	public function __construct( Deserializer $siteLinkListDeserializer ) {
		$this->siteLinkListDeserializer = $siteLinkListDeserializer;
	}

	/**
	 * @param mixed $serialization
	 *
	 * @return Snak
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'Item serialization should be an array' );
		}

		$this->serialization = $serialization;
		$this->item = Item::newEmpty();

		$this->addSiteLinks();

		return $this->item;
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

}