<?php

declare( strict_types = 1 );

namespace Wikibase\DataModel\Serializers;

use Serializers\DispatchableSerializer;
use Serializers\Exceptions\SerializationException;
use Serializers\Exceptions\UnsupportedObjectException;
use Wikibase\DataModel\Entity\Item;

/**
 * Package private
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 * @author Jan Zerebecki < jan.wikimedia@zerebecki.de >
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ItemSerializer implements DispatchableSerializer {

	private TermListSerializer $termListSerializer;
	private AliasGroupListSerializer $aliasGroupListSerializer;
	private StatementListSerializer $statementListSerializer;
	private SiteLinkListSerializer $siteLinkListSerializer;

	public function __construct(
		TermListSerializer $termListSerializer,
		AliasGroupListSerializer $aliasGroupListSerializer,
		StatementListSerializer $statementListSerializer,
		SiteLinkSerializer $siteLinkSerializer,
		bool $useObjectsForEmptyMaps
	) {
		$this->termListSerializer = $termListSerializer;
		$this->aliasGroupListSerializer = $aliasGroupListSerializer;
		$this->statementListSerializer = $statementListSerializer;
		$this->siteLinkListSerializer = new SiteLinkListSerializer( $siteLinkSerializer, $useObjectsForEmptyMaps );
	}

	/**
	 * @see Serializer::isSerializerFor
	 *
	 * @param mixed $object
	 *
	 * @return bool
	 */
	public function isSerializerFor( $object ) {
		return $object instanceof Item;
	}

	/**
	 * @see Serializer::serialize
	 *
	 * @param Item $object
	 *
	 * @throws SerializationException
	 * @return array
	 */
	public function serialize( $object ) {
		if ( !$this->isSerializerFor( $object ) ) {
			throw new UnsupportedObjectException(
				$object,
				'ItemSerializer can only serialize Item objects.'
			);
		}

		return $this->getSerialized( $object );
	}

	private function getSerialized( Item $item ): array {
		$serialization = [
			'type' => $item->getType(),
		];

		$this->addIdToSerialization( $item, $serialization );
		$this->addTermsToSerialization( $item, $serialization );
		$this->addStatementListToSerialization( $item, $serialization );
		$this->addSiteLinksToSerialization( $item, $serialization );

		return $serialization;
	}

	private function addIdToSerialization( Item $item, array &$serialization ): void {
		$id = $item->getId();

		if ( $id !== null ) {
			$serialization['id'] = $id->getSerialization();
		}
	}

	private function addTermsToSerialization( Item $item, array &$serialization ): void {
		$fingerprint = $item->getFingerprint();

		$serialization['labels'] = $this->termListSerializer->serialize( $fingerprint->getLabels() );
		$serialization['descriptions'] =
			$this->termListSerializer->serialize( $fingerprint->getDescriptions() );
		$serialization['aliases'] =
			$this->aliasGroupListSerializer->serialize( $fingerprint->getAliasGroups() );
	}

	private function addStatementListToSerialization( Item $item, array &$serialization ): void {
		$serialization['claims'] = $this->statementListSerializer->serialize( $item->getStatements() );
	}

	private function addSiteLinksToSerialization( Item $item, array &$serialization ): void {
		$serialization['sitelinks'] = $this->siteLinkListSerializer->serialize( $item->getSiteLinkList() );
	}

}
