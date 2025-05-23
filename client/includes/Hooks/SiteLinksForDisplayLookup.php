<?php

namespace Wikibase\Client\Hooks;

use MediaWiki\Title\Title;
use Psr\Log\LoggerInterface;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * Returns the site links to display in the navigation areas of the client UI
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class SiteLinksForDisplayLookup {

	/** @var SiteLinkLookup */
	private $siteLinkLookup;

	/** @var EntityLookup */
	private $entityLookup;

	/** @var UsageAccumulator */
	private $usageAccumulator;

	/** @var WikibaseClientSiteLinksForItemHook */
	private $hookRunner;

	/** @var LoggerInterface */
	private $logger;

	/** @var string */
	private $siteId;

	/**
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param EntityLookup $entityLookup
	 * @param UsageAccumulator $usageAccumulator
	 * @param WikibaseClientSiteLinksForItemHook $hookRunner
	 * @param LoggerInterface $logger
	 * @param string $siteId The global site ID for the local wiki
	 */
	public function __construct(
		SiteLinkLookup $siteLinkLookup,
		EntityLookup $entityLookup,
		UsageAccumulator $usageAccumulator,
		WikibaseClientSiteLinksForItemHook $hookRunner,
		LoggerInterface $logger,
		string $siteId
	) {
		$this->siteLinkLookup = $siteLinkLookup;
		$this->entityLookup = $entityLookup;
		$this->usageAccumulator = $usageAccumulator;
		$this->hookRunner = $hookRunner;
		$this->logger = $logger;
		$this->siteId = $siteId;
	}

	/**
	 * Finds the corresponding item on the repository and returns the item's site links to display in the UI.
	 *
	 * Runs the WikibaseClientSiteLinksForItem hook to allow extensions to add more site links
	 * based on e.g. statements or connected entities.
	 *
	 * @param Title $title
	 *
	 * @return SiteLink[] A map of SiteLinks, indexed by global site id.
	 */
	public function getSiteLinksForPageTitle( Title $title ) {
		$itemId = $this->siteLinkLookup->getItemIdForLink( $this->siteId, $title->getPrefixedText() );

		if ( $itemId === null ) {
			return [];
		}

		return $this->getSiteLinksForItemId( $itemId );
	}

	/**
	 * Returns the item's site links to display in the UI.
	 *
	 * Runs the WikibaseClientSiteLinksForItem hook to allow extensions to add more site links
	 * based on e.g. statements or connected entities.
	 *
	 * @param ItemId $itemId
	 *
	 * @return SiteLink[] A map of SiteLinks, indexed by global site id.
	 */
	public function getSiteLinksForItemId( ItemId $itemId ) {
		$item = $this->entityLookup->getEntity( $itemId );

		if ( $item === null ) {
			$this->logger->warning( __METHOD__ . ': Could not load item ' . $itemId->getSerialization() );
			return [];
		}

		'@phan-var \Wikibase\DataModel\Entity\Item $item';
		return $this->getSiteLinksForItem( $item );
	}

	private function getSiteLinksForItem( Item $item ): array {
		$siteLinks = $item->getSiteLinkList()->toArray();

		$this->hookRunner->onWikibaseClientSiteLinksForItem(
			$item, $siteLinks, $this->usageAccumulator
		);

		return $siteLinks;
	}
}
