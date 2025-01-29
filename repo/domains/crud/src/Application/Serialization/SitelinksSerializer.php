<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use ArrayObject;
use Wikibase\Repo\RestApi\Domain\ReadModel\Sitelinks;

/**
 * @license GPL-2.0-or-later
 */
class SitelinksSerializer {

	private SitelinkSerializer $sitelinkSerializer;

	public function __construct( SitelinkSerializer $sitelinkSerializer ) {
		$this->sitelinkSerializer = $sitelinkSerializer;
	}

	public function serialize( Sitelinks $sitelinks ): ArrayObject {
		$serialization = new ArrayObject();

		foreach ( $sitelinks as $sitelink ) {
			$serialization[$sitelink->getSiteId()] = $this->sitelinkSerializer->serialize( $sitelink );
		}

		return $serialization;
	}
}
