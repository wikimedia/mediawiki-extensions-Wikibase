<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\Serialization;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Sitelink;

/**
 * @license GPL-2.0-or-later
 */
class SitelinkSerializer {
	public function serialize( Sitelink $sitelink ): array {
		return [
			'title' => $sitelink->getTitle(),
			'badges' => array_map(
				fn( ItemId $badge ) => $badge->getSerialization(),
				$sitelink->getBadges()
			),
			'url' => $sitelink->getUrl(),
		];
	}
}
