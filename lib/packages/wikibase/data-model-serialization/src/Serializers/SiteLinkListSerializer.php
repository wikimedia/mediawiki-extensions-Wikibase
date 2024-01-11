<?php

namespace Wikibase\DataModel\Serializers;

use Serializers\Serializer;
use Wikibase\DataModel\SiteLinkList;

/**
 * @license GPL-2.0-or-later
 */
class SiteLinkListSerializer {

	private $siteLinkSerializer;

	public function __construct( Serializer $siteLinkSerializer ) {
		$this->siteLinkSerializer = $siteLinkSerializer;
	}

	public function serialize( SiteLinkList $siteLinkList ) {
		$serialization = [];
		foreach ( $siteLinkList->toArray() as $siteLink ) {
			$serialization[$siteLink->getSiteId()] = $this->siteLinkSerializer->serialize( $siteLink );
		}

		return $serialization;
	}

}
