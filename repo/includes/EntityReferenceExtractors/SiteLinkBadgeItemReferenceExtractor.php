<?php

namespace Wikibase\Repo\EntityReferenceExtractors;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikimedia\Assert\Assert;

/**
 * Extracts ids of items that are used as badges on site links on a given item.
 *
 * @license GPL-2.0-or-later
 */
class SiteLinkBadgeItemReferenceExtractor implements EntityReferenceExtractor {

	/**
	 * @param EntityDocument $item
	 * @return EntityId[]
	 */
	public function extractEntityIds( EntityDocument $item ) {
		Assert::parameterType( Item::class, $item, '$item' );

		/**
		 * @var Item $item
		 */
		 '@phan-var Item $item';
		return $this->extractItemIdsFromSiteLinks( $item->getSiteLinkList() );
	}

	private function extractBadgeIds( SiteLink $siteLink ) {
		$ids = [];

		foreach ( $siteLink->getBadges() as $badge ) {
			$ids[$badge->getSerialization()] = $badge;
		}

		return $ids;
	}

	private function extractItemIdsFromSiteLinks( SiteLinkList $siteLinks ) {
		$ids = [];

		foreach ( $siteLinks as $siteLink ) {
			$ids = array_merge( $ids, $this->extractBadgeIds( $siteLink ) );
		}

		return array_values( $ids );
	}

}
