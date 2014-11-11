<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLinkList;

/**
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class ItemDeserializer extends EntityDeserializer {

	/**
	 * @var Deserializer
	 */
	private $siteLinkDeserializer;

	/**
	 * @param Deserializer $entityIdDeserializer
	 * @param Deserializer $claimsDeserializer
	 * @param Deserializer $siteLinkDeserializer
	 */
	public function __construct(
		Deserializer $entityIdDeserializer,
		Deserializer $claimsDeserializer,
		Deserializer $siteLinkDeserializer
	) {
		parent::__construct( 'item', $entityIdDeserializer, $claimsDeserializer );

		$this->siteLinkDeserializer = $siteLinkDeserializer;
	}

	protected function getPartiallyDeserialized( array $serialization ) {
		$item = Item::newEmpty();

		$this->setSiteLinksFromSerialization( $item->getSiteLinkList(), $serialization );

		return $item;
	}

	private function setSiteLinksFromSerialization(
		SiteLinkList $siteLinkList,
		array $serialization
	) {
		if ( !array_key_exists( 'sitelinks', $serialization ) ) {
			return;
		}
		$this->assertAttributeIsArray( $serialization, 'sitelinks' );

		foreach( $serialization['sitelinks'] as $siteLinksSerialization ) {
			$siteLinkList->addSiteLink(
				$this->siteLinkDeserializer->deserialize( $siteLinksSerialization )
			);
		}
	}

}
