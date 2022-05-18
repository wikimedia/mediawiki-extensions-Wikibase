<?php

namespace Wikibase\DataModel\Serializers;

use Serializers\Serializer;
use Wikibase\DataModel\SiteLinkList;

/**
 * @license GPL-2.0-or-later
 */
class SiteLinkListSerializer {

	private $siteLinkSerializer;
	private $useObjectsForMaps;

	public function __construct( Serializer $siteLinkSerializer, bool $useObjectsForMaps ) {
		$this->siteLinkSerializer = $siteLinkSerializer;
		$this->useObjectsForMaps = $useObjectsForMaps;
	}

	public function serialize( SiteLinkList $siteLinkList ) {
		$serialization = [];
		foreach ( $siteLinkList->toArray() as $siteLink ) {
			$serialization[$siteLink->getSiteId()] = $this->siteLinkSerializer->serialize( $siteLink );
		}

		return $this->useObjectsForMaps ? (object)$serialization : $serialization;
	}

}
