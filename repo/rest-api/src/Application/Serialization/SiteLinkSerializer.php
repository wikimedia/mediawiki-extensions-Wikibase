<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\ReadModel\SiteLink;

/**
 * @license GPL-2.0-or-later
 */
class SiteLinkSerializer {
	public function serialize( SiteLink $siteLink ): array {
		return [
			'title' => $siteLink->getTitle(),
			'badges' => array_map(
				fn( ItemId $badge ) => $badge->getSerialization(),
				$siteLink->getBadges()
			),
			'url' => $siteLink->getUrl(),
		];
	}
}
