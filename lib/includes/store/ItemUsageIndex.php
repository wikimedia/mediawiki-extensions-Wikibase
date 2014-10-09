<?php

namespace Wikibase;

use Site;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * Index for tracking the usage of entities on a specific client wiki.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ItemUsageIndex {

	protected $clientSite;
	protected $siteLinks;

	/**
	 * @param Site $clientSite
	 * @param SiteLinkLookup $siteLinks
	 */
	public function __construct( Site $clientSite, SiteLinkLookup $siteLinks ) {
		$this->clientSite = $clientSite;
		$this->siteLinks = $siteLinks;
	}

	/**
	 * Returns the Site of the client wiki this usage index is tracking.
	 *
	 * @since 0.4
	 *
	 * @return Site
	 */
	public function getClientSite() {
		return $this->clientSite;
	}

	/**
	 * Determines which pages use any of the given items.
	 *
	 * @since 0.4
	 *
	 * @param ItemId[] $itemIds
	 *
	 * @return string[] list of pages using any of the given entities
	 */
	public function getEntityUsage( array $itemIds ) {
		if ( empty( $itemIds ) ) {
			return array();
		}

		$ids = array_map(
			function ( ItemId $id ) {
				return $id->getNumericId();
			},
			$itemIds
		);

		$rows = $this->siteLinks->getLinks( $ids, array( $this->clientSite->getGlobalId() ) );

		$pages = array_map(
			function ( array $row ) {
				return $row[1]; // page name
			},
			$rows
		);

		return array_unique( $pages );
	}

	/**
	 * Checks which of the given items is used on the target wiki,
	 * and removed all others.
	 *
	 * @since 0.4
	 *
	 * @param ItemId[] $itemIds The entities to check
	 * @param string|null $type     The entity type to check. This is an optional hint that may
	 *                              be used for optimization. If given, all IDs in the $entities
	 *                              array must refer to entities of the given type.
	 *
	 * @return ItemId[] the items actually used on the target wiki
	 * @throws \MWException if $type is set and one of the ids in $entities
	 */
	public function filterUnusedEntities( array $itemIds, $type = null ) {
		if ( empty( $itemIds ) ) {
			return array();
		}

		if ( $type !== null && $type !== Item::ENTITY_TYPE ) {
			return array();
		}

		$ids = array_map(
			function ( ItemId $id ) use ( $type ) {
				if ( $type !== null && $id->getEntityType() !== $type ) {
					throw new \MWException( "Optimizing for $type, encountered ID for " . $id->getEntityType() );
				}

				return $id->getNumericId();
			},
			$itemIds
		);

		//todo: pass the type hint to the SiteLinksLookup, to allow for more efficient queries
		$rows = $this->siteLinks->getLinks( $ids, array( $this->clientSite->getGlobalId() ) ) ;

		$used = array_map(
			function ( array $row ) {
				return intval( $row[2] ); // item id
			},
			$rows
		);

		$used = array_flip( $used );

		$filtered = array_filter(
			$itemIds,
			function ( ItemId $id ) use ( $used ) {
				return array_key_exists( $id->getNumericId(), $used );
			}
		);

		return $filtered;

	}

	/**
	 * Determines which items are used by any of the given pages.
	 *
	 * The page titles must be strings in the canonical form, as returned
	 * by Title::getPrefixedText() on the target wiki. Note that it is not
	 * reliable to use Title objects locally to represent pages on another wiki,
	 * since namespaces and normalization rules may differ.
	 *
	 * @since 0.4
	 *
	 * @param string[] $pages The titles of the pages to check.
	 *
	 * @return ItemId[]
	 */
	public function getUsedEntities( array $pages ) {
		if ( empty( $pages ) ) {
			return array();
		}

		$entities = array();

		//todo: implement batched lookup in SiteLinkLookup
		foreach ( $pages as $page ) {
			$id = $this->siteLinks->getItemIdForLink( $this->clientSite->getGlobalId(), $page );

			if ( $id !== null ) {
				//Note: we are using the ID as the key here to make sure each item only
				//      shows up once.
				$entities[$id->getSerialization()] = $id;
			}
		}

		return $entities;
	}

}
