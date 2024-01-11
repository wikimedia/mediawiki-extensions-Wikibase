<?php

namespace Wikibase\DataModel\Serializers;

use Serializers\Serializer;
use Wikibase\DataModel\SiteLinkList;

/**
 * @license GPL-2.0-or-later
 */
class SiteLinkListSerializer extends MapSerializer {

	private $siteLinkSerializer;

	public function __construct( Serializer $siteLinkSerializer, bool $useObjectsForEmptyMaps ) {
		parent::__construct( $useObjectsForEmptyMaps );
		$this->siteLinkSerializer = $siteLinkSerializer;
	}

	public function serialize( SiteLinkList $siteLinkList ) {
		return $this->serializeMap( $this->generateSerializedArrayRepresentation( $siteLinkList ) );
	}

	protected function generateSerializedArrayRepresentation( SiteLinkList $siteLinkList ): array {
		$serialization = [];
		foreach ( $siteLinkList->toArray() as $siteLink ) {
			$serialization[$siteLink->getSiteId()] = $this->siteLinkSerializer->serialize( $siteLink );
		}

		return $serialization;
	}
}
