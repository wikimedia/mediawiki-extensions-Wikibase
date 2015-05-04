<?php

namespace Wikibase\DataModel\Serializers;

use Serializers\DispatchableSerializer;
use Serializers\Exceptions\SerializationException;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\DataModel\Entity\Item;

/**
 * Package private
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 * @author Jan Zerebecki < jan.wikimedia@zerebecki.de >
 */
class ItemSerializer implements DispatchableSerializer {

	/**
	 * @var FingerprintSerializer
	 */
	private $fingerprintSerializer;

	/**
	 * @var Serializer
	 */
	private $statementListSerializer;

	/**
	 * @var Serializer
	 */
	private $siteLinkSerializer;

	/**
	 * @var bool
	 */
	private $useObjectsForMaps;

	/**
	 * @param FingerprintSerializer $fingerprintSerializer
	 * @param Serializer $statementListSerializer
	 * @param Serializer $siteLinkSerializer
	 * @param bool $useObjectsForMaps
	 */
	public function __construct(
		FingerprintSerializer $fingerprintSerializer,
		Serializer $statementListSerializer,
		Serializer $siteLinkSerializer,
		$useObjectsForMaps
	) {
		$this->fingerprintSerializer = $fingerprintSerializer;
		$this->statementListSerializer = $statementListSerializer;
		$this->siteLinkSerializer = $siteLinkSerializer;
		$this->useObjectsForMaps = $useObjectsForMaps;
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
	 * @return array
	 * @throws SerializationException
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

	private function getSerialized( Item $item ) {
		$serialization = array(
			'type' => $item->getType()
		);

		$this->fingerprintSerializer->addBasicsToSerialization( $item, $serialization );
		$this->addStatementListToSerialization( $item, $serialization );
		$this->addSiteLinksToSerialization( $item, $serialization );

		return $serialization;
	}

	private function addStatementListToSerialization( Item $item, array &$serialization ) {
		$serialization['claims'] = $this->statementListSerializer->serialize( $item->getStatements() );
	}

	private function addSiteLinksToSerialization( Item $item, array &$serialization ) {
		$siteLinks = $item->getSiteLinks();

		$serialization['sitelinks'] = array();

		foreach( $siteLinks as $siteLink ) {
			$serialization['sitelinks'][$siteLink->getSiteId()] = $this->siteLinkSerializer->serialize( $siteLink );
		}
		if ( $this->useObjectsForMaps ) {
			$serialization['sitelinks'] = (object)$serialization['sitelinks'];
		}
	}

}
