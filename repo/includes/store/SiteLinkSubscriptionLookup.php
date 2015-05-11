<?php

namespace Wikibase\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * Implementation of SubscriptionLookup based on a SiteLinkStore.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class SiteLinkSubscriptionLookup implements SubscriptionLookup {

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @param SiteLinkLookup $siteLinkLookup
	 */
	public function __construct( SiteLinkLookup $siteLinkLookup ) {
		$this->siteLinkLookup = $siteLinkLookup;
	}

	/**
	 * Returns a list of entities a given site is subscribed to.
	 *
	 * @param string $siteId Site ID of the client site.
	 * @param EntityId[]|null $entityIds The entities we are interested in, or null for "any".
	 *
	 * @return EntityId[] a list of entity IDs the client wiki is subscribed to.
	 *         The result is limited to entity ids also present in $entityIds, if given.
	 */
	public function getSubscriptions( $siteId, array $entityIds ) {
		// NOTE: non-Item ids are ignored, since only items can be subscribed to
		//       via sitelinks.
		$entityIds = $this->getItemIds( $entityIds );
		$numericIds = array_keys( $entityIds );

		if ( empty( $numericIds ) ) {
			return array();
		}

		$links = $this->siteLinkLookup->getLinks( $numericIds, array( $siteId ) );

		// collect the item IDs present in these links
		$linkedItems = array();
		foreach ( $links as $link ) {
			list(,, $id ) = $link;
			$linkedItems[$id] = $entityIds[$id];
		}

		return $linkedItems;
	}

	/**
	 * @param EntityId[] $entityIds
	 *
	 * @return ItemId[] The ItemIds from EntityId[], keyed by numeric id.
	 */
	private function getItemIds( array $entityIds ) {
		$reindexed = array();

		foreach ( $entityIds as $id ) {
			if ( $id instanceof ItemId ) {
				$key = $id->getNumericId();
				$reindexed[$key] = $id;
			}
		}

		return $reindexed;
	}

}
