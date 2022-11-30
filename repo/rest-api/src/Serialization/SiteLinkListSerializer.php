<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Serialization;

use ArrayObject;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLinkList;

/**
 * @license GPL-2.0-or-later
 */
class SiteLinkListSerializer {

	public function serialize( SiteLinkList $siteLinkList ): ArrayObject {
		$serialization = new ArrayObject();

		foreach ( $siteLinkList->toArray() as $siteLink ) {

			$serialization[$siteLink->getSiteId()] = [
				'title' => $siteLink->getPageName(),
				'badges' => array_map(
					fn( ItemId $badge ) => $badge->getSerialization(),
					$siteLink->getBadges()
				)
			];
		}

		return $serialization;
	}
}
