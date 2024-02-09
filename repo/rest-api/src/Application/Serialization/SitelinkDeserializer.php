<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;

/**
 * @license GPL-2.0-or-later
 */
class SitelinkDeserializer {

	public function deserialize( string $siteId, array $serialization ): SiteLink {
		$serialization['badges'] ??= [];

		$badges = [];
		foreach ( $serialization[ 'badges' ] as $badge ) {
			$badges[] = new ItemId( $badge );
		}

		return new SiteLink( $siteId, $serialization[ 'title' ], $badges );
	}

}
