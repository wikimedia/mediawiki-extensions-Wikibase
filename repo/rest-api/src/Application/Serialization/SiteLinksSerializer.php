<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use ArrayObject;
use Wikibase\Repo\RestApi\Domain\ReadModel\SiteLinks;

/**
 * @license GPL-2.0-or-later
 */
class SiteLinksSerializer {

	private SiteLinkSerializer $siteLinkSerializer;

	public function __construct( SiteLinkSerializer $siteLinkSerializer ) {
		$this->siteLinkSerializer = $siteLinkSerializer;
	}

	public function serialize( SiteLinks $siteLinks ): ArrayObject {
		$serialization = new ArrayObject();

		foreach ( $siteLinks as $siteLink ) {
			$serialization[$siteLink->getSite()] = $this->siteLinkSerializer->serialize( $siteLink );
		}

		return $serialization;
	}
}
