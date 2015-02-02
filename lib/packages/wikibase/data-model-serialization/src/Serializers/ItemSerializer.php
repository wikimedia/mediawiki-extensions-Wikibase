<?php

namespace Wikibase\DataModel\Serializers;

use Serializers\DispatchableSerializer;
use Serializers\Exceptions\SerializationException;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\DataModel\Claim\Claims;
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
	private $claimsSerializer;

	/**
	 * @var Serializer
	 */
	private $siteLinkSerializer;

	/**
	 * @var bool
	 */
	protected $useObjectsForMaps;

	/**
	 * @param FingerprintSerializer $fingerprintSerializer
	 * @param Serializer $claimsSerializer
	 * @param Serializer $siteLinkSerializer
	 * @param bool $useObjectsForMaps
	 */
	public function __construct( FingerprintSerializer $fingerprintSerializer, Serializer $claimsSerializer, Serializer $siteLinkSerializer, $useObjectsForMaps ) {
		$this->fingerprintSerializer = $fingerprintSerializer;
		$this->claimsSerializer = $claimsSerializer;
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
	 * @param mixed $object
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
		$this->addClaimsToSerialization( $item, $serialization );
		$this->addSiteLinksToSerialization( $item, $serialization );

		return $serialization;
	}

	private function addClaimsToSerialization( Item $item, array &$serialization ) {
		$claims = new Claims( $item->getClaims() );

		$serialization['claims'] = $this->claimsSerializer->serialize( $claims );
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
