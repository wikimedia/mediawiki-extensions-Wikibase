<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Serialization;

use ArrayObject;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\ReadModel\SiteLinks;

/**
 * @license GPL-2.0-or-later
 */
class SiteLinkListSerializer {

	public function serialize( SiteLinks $siteLinks ): ArrayObject {
		$serialization = new ArrayObject();

		foreach ( $siteLinks as $siteLink ) {
			$serialization[$siteLink->getSite()] = [
				'title' => $siteLink->getTitle(),
				'badges' => array_map(
					fn( ItemId $badge ) => $badge->getSerialization(),
					$siteLink->getBadges()
				),
			];
		}

		return $serialization;
	}
}
