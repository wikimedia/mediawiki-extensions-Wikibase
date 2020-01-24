<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\TypedObjectDeserializer;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\TermList;

/**
 * Package private
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ItemDeserializer extends TypedObjectDeserializer {

	/**
	 * @var Deserializer
	 */
	private $entityIdDeserializer;

	/**
	 * @var Deserializer
	 */
	private $termListDeserializer;

	/**
	 * @var Deserializer
	 */
	private $aliasGroupListDeserializer;

	/**
	 * @var Deserializer
	 */
	private $statementListDeserializer;

	/**
	 * @var Deserializer
	 */
	private $siteLinkDeserializer;

	public function __construct(
		Deserializer $entityIdDeserializer,
		Deserializer $termListDeserializer,
		Deserializer $aliasGroupListDeserializer,
		Deserializer $statementListDeserializer,
		Deserializer $siteLinkDeserializer
	) {
		parent::__construct( 'item', 'type' );

		$this->entityIdDeserializer = $entityIdDeserializer;
		$this->termListDeserializer = $termListDeserializer;
		$this->aliasGroupListDeserializer = $aliasGroupListDeserializer;
		$this->statementListDeserializer = $statementListDeserializer;
		$this->siteLinkDeserializer = $siteLinkDeserializer;
	}

	/**
	 * @see Deserializer::deserialize
	 *
	 * @param array $serialization
	 *
	 * @throws DeserializationException
	 * @return Item
	 */
	public function deserialize( $serialization ) {
		$this->assertCanDeserialize( $serialization );

		return $this->getDeserialized( $serialization );
	}

	private function getDeserialized( array $serialization ) {
		$item = new Item();

		$this->setIdFromSerialization( $serialization, $item );
		$this->setTermsFromSerialization( $serialization, $item );
		$this->setStatementListFromSerialization( $serialization, $item );
		$this->setSiteLinksFromSerialization( $item->getSiteLinkList(), $serialization );

		return $item;
	}

	private function setIdFromSerialization( array $serialization, Item $item ) {
		if ( !array_key_exists( 'id', $serialization ) ) {
			return;
		}

		/** @var ItemId $id */
		$id = $this->entityIdDeserializer->deserialize( $serialization['id'] );
		$item->setId( $id );
	}

	private function setTermsFromSerialization( array $serialization, Item $item ) {
		if ( array_key_exists( 'labels', $serialization ) ) {
			$this->assertAttributeIsArray( $serialization, 'labels' );
			/** @var TermList $labels */
			$labels = $this->termListDeserializer->deserialize( $serialization['labels'] );
			$item->getFingerprint()->setLabels( $labels );
		}

		if ( array_key_exists( 'descriptions', $serialization ) ) {
			$this->assertAttributeIsArray( $serialization, 'descriptions' );
			/** @var TermList $descriptions */
			$descriptions = $this->termListDeserializer->deserialize( $serialization['descriptions'] );
			$item->getFingerprint()->setDescriptions( $descriptions );
		}

		if ( array_key_exists( 'aliases', $serialization ) ) {
			$this->assertAttributeIsArray( $serialization, 'aliases' );
			/** @var AliasGroupList $aliases */
			$aliases = $this->aliasGroupListDeserializer->deserialize( $serialization['aliases'] );
			$item->getFingerprint()->setAliasGroups( $aliases );
		}
	}

	private function setStatementListFromSerialization( array $serialization, Item $item ) {
		if ( !array_key_exists( 'claims', $serialization ) ) {
			return;
		}

		/** @var StatementList $statements */
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

		foreach ( $serialization['sitelinks'] as $siteLinksSerialization ) {
			/** @var SiteLink $siteLink */
			$siteLink = $this->siteLinkDeserializer->deserialize( $siteLinksSerialization );
			$siteLinkList->addSiteLink( $siteLink );
		}
	}

}
