<?php

namespace Wikibase\Client\Usage;

use ArrayIterator;
use InvalidArgumentException;
use Iterator;
use Wikibase\Client\Store\TitleFactory;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * UsageLookup implementation based on a SiteLinkLookup.
 * This tracks the usage of directly connected items as EntityUsage::SITELINK_USAGE.
 * Other types of usage are not tracked.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class SiteLinkUsageLookup implements UsageLookup {

	/**
	 * @var string
	 */
	protected $clientSiteId;

	/**
	 * @var SiteLinkLookup
	 */
	protected $siteLinks;

	/**
	 * @var TitleFactory
	 */
	private $titleFactory;

	/**
	 * @param string $clientSiteId The local wiki's global site id
	 * @param SiteLinkLookup $siteLinks
	 * @param TitleFactory $titleFactory
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct( $clientSiteId, SiteLinkLookup $siteLinks, TitleFactory $titleFactory ) {
		if ( !is_string( $clientSiteId ) ) {
			throw new InvalidArgumentException( '$clientSiteId must be a string' );
		}

		$this->clientSiteId = $clientSiteId;
		$this->siteLinks = $siteLinks;
		$this->titleFactory = $titleFactory;
	}

	/**
	 * @see UsageLookup::getUsageForPage
	 *
	 * @param int $pageId
	 *
	 * @return EntityUsage[]
	 * @throws UsageTrackerException
	 */
	public function getUsageForPage( $pageId ) {
		$usages = array();

		$id = $this->siteLinks->getItemIdForLink( $this->clientSiteId, $pageId );

		if ( $id !== null ) {
			$usages[] = new EntityUsage( $id, EntityUsage::SITELINK_USAGE );
		}

		return $usages;
	}

	/**
	 * @see UsageLookup::getPagesUsing
	 *
	 * @param EntityId[] $entityIds
	 * @param string[] $aspects Which aspects to consider (if omitted, all aspects are considered).
	 * Use the EntityUsage::XXX_USAGE constants to represent aspects.
	 *
	 * @return Iterator An iterator over the IDs of pages using any of the given entities.
	 *         If $aspects is given, only usages of these aspects are included in the result.
	 * @throws UsageTrackerException
	 */
	public function getPagesUsing( array $entityIds, array $aspects = array() ) {
		if ( empty( $entityIds ) ) {
			return new ArrayIterator( array() );
		}

		if ( $aspects && !in_array( EntityUsage::SITELINK_USAGE, $aspects ) ) {
			return new ArrayIterator( array() );
		}

		$numericItemIds = $this->getNumericItemIds( $entityIds );
		$rows = $this->siteLinks->getLinks( $numericItemIds, array( $this->clientSiteId ) );

		$pageIds = $this->getPageIdsFromSiteLinkRows( $rows );
		return new ArrayIterator( $pageIds );
	}

	/**
	 * Extracts numeric IDs from ItemIds; Other EntityIds are ignored.
	 * 
	 * @param EntityId[] $ids
	 *
	 * @return int[]
	 */
	private function getNumericItemIds( array $ids ) {
		$ids = array_filter( $ids, function ( EntityId $id ) {
			return $id instanceof ItemId;
		} );

		return array_map(
			function ( ItemId $id ) {
				return $id->getNumericId();
			},
			$ids
		);
	}

	/**
	 * @param array[] $rows Rows as returned by SiteLinkLookup::getLinks
	 *
	 * @return int[]
	 */
	private function getPageIdsFromSiteLinkRows( array $rows ) {
		$titleFactory = $this->titleFactory;
		$pageIds = array_map(
			function ( array $row ) use ( $titleFactory ) {
				$title = $titleFactory->newFromText( $row[1] );
				return $title ? $title->getArticleID() : 0;
			},
			$rows
		);

		array_unique( $pageIds );
		return $pageIds;
	}

	/**
	 * @param array[] $rows Rows as returned by SiteLinkLookup::getLinks
	 *
	 * @return int[]
	 */
	private function getItemIdsFromSiteLinkRows( array $rows ) {
		$itemIds = array_map(
			function ( array $row ) {
				return intval( $row[2] );
			},
			$rows
		);

		array_unique( $itemIds );
		return $itemIds;
	}

	/**
	 * @param int[] $numericIds
	 *
	 * @return ItemId[]
	 */
	private function makeItemIds( $numericIds ) {
		return array_map(
			function ( $id ) {
				return ItemId::newFromNumber( $id );
			},
			$numericIds
		);
	}

	/**
	 * @see UsageLookup::getUnusedEntities
	 *
	 * @param EntityId[] $entityIds
	 *
	 * @return EntityId[] A list of elements of $entities that are unused.
	 */
	public function getUnusedEntities( array $entityIds ) {
		if ( empty( $entityIds ) ) {
			return array();
		}

		// Non-item entities are always considered unused by this implementation.
		$nonItemIds = array_filter( $entityIds, function ( EntityId $id ) {
			return !( $id instanceof ItemId );
		} );

		$ids = $this->getNumericItemIds( $entityIds );

		$rows = $this->siteLinks->getLinks( $ids, array( $this->clientSiteId ) ) ;

		$used = $this->getItemIdsFromSiteLinkRows( $rows );
		$unusedIds = array_diff( $ids, $used );

		return array_merge(
			$nonItemIds,
			$this->makeItemIds( $unusedIds )
		);
	}

}
