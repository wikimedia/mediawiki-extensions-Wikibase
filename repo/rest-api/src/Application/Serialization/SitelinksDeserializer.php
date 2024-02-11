<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use Wikibase\DataModel\SiteLinkList;

/**
 * @license GPL-2.0-or-later
 */
class SitelinksDeserializer {

	private SitelinkDeserializer $sitelinkDeserializer;

	public function __construct( SitelinkDeserializer $sitelinkDeserializer ) {
		$this->sitelinkDeserializer = $sitelinkDeserializer;
	}

	public function deserialize( array $serialization ): SiteLinkList {
		$sitelinks = [];

		foreach ( $serialization as $siteId => $sitelink ) {
			$sitelinks[ $siteId ] = $this->sitelinkDeserializer->deserialize( $siteId, $sitelink );
		}

		return new SiteLinkList( $sitelinks );
	}

}
