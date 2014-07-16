<?php

namespace Wikibase\DataModel\Serializers;

use Serializers\Serializer;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;

/**
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class ItemSerializer extends EntitySerializer {

	/**
	 * @var Serializer
	 */
	private $siteLinkSerializer;

	/**
	 * @param Serializer $claimsSerializer
	 * @param Serializer $siteLinkSerializer
	 */
	public function __construct( Serializer $claimsSerializer, Serializer $siteLinkSerializer ) {
		parent::__construct( $claimsSerializer );

		$this->siteLinkSerializer = $siteLinkSerializer;
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

	protected function getSpecificSerialization( Entity $entity ) {
		$serialization = array();

		$this->addSiteLinksToSerialization( $entity, $serialization );

		return $serialization;
	}

	private function addSiteLinksToSerialization( Item $item, array &$serialization ) {
		$siteLinks = $item->getSiteLinks();

		$serialization['sitelinks'] = array();

		foreach( $siteLinks as $siteLink ) {
			$serialization['sitelinks'][$siteLink->getSiteId()] = $this->siteLinkSerializer->serialize( $siteLink );
		}
	}

}
