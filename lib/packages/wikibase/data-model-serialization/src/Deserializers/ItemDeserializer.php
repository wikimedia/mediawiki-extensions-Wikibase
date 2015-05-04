<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\TypedObjectDeserializer;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLinkList;

/**
 * Package private
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class ItemDeserializer extends TypedObjectDeserializer {

	/**
	 * @var Deserializer
	 */
	private $entityIdDeserializer;

	/**
	 * @var Deserializer
	 */
	private $fingerprintDeserializer;

	/**
	 * @var Deserializer
	 */
	private $statementListDeserializer;

	/**
	 * @var Deserializer
	 */
	private $siteLinkDeserializer;

	/**
	 * @param Deserializer $entityIdDeserializer
	 * @param Deserializer $fingerprintDeserializer
	 * @param Deserializer $statementListDeserializer
	 * @param Deserializer $siteLinkDeserializer
	 */
	public function __construct(
		Deserializer $entityIdDeserializer,
		Deserializer $fingerprintDeserializer,
		Deserializer $statementListDeserializer,
		Deserializer $siteLinkDeserializer
	) {
		parent::__construct( 'item', 'type' );

		$this->entityIdDeserializer = $entityIdDeserializer;
		$this->fingerprintDeserializer = $fingerprintDeserializer;
		$this->statementListDeserializer = $statementListDeserializer;
		$this->siteLinkDeserializer = $siteLinkDeserializer;
	}

	/**
	 * @see Deserializer::deserialize
	 *
	 * @param array $serialization
	 *
	 * @return Item
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		$this->assertCanDeserialize( $serialization );

		return $this->getDeserialized( $serialization );
	}

	private function getDeserialized( array $serialization ) {
		$item = new Item();

		$item->setFingerprint( $this->fingerprintDeserializer->deserialize( $serialization ) );

		$this->setIdFromSerialization( $serialization, $item );
		$this->setStatementListFromSerialization( $serialization, $item );
		$this->setSiteLinksFromSerialization( $item->getSiteLinkList(), $serialization );

		return $item;
	}

	private function setIdFromSerialization( array $serialization, Item $item ) {
		if ( !array_key_exists( 'id', $serialization ) ) {
			return;
		}

		$item->setId( $this->entityIdDeserializer->deserialize( $serialization['id'] ) );
	}

	private function setStatementListFromSerialization( array $serialization, Item $item ) {
		if ( !array_key_exists( 'claims', $serialization ) ) {
			return;
		}

		$statements = $this->statementListDeserializer->deserialize( $serialization['claims'] );
		$item->setStatements( $statements );
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
