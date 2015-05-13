<?php

namespace Wikibase\Client;

use InvalidArgumentException;
use ParserOutput;
use Title;
use Wikibase\Client\Hooks\OtherProjectsSidebarGeneratorFactory;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * Update Wikibase ParserOutput properties and extension data.

 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ParserOutputDataUpdater {

	/**
	 * @var OtherProjectsSidebarGeneratorFactory
	 */
	private $otherProjectsSidebarGeneratorFactory;

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @var string
	 */
	private $siteId;

	/**
	 * @param OtherProjectsSidebarGeneratorFactory $otherProjectsSidebarGeneratorFactory
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param string $siteId The global site ID for the local wiki
	 */
	public function __construct(
		OtherProjectsSidebarGeneratorFactory $otherProjectsSidebarGeneratorFactory,
		SiteLinkLookup $siteLinkLookup,
		$siteId
	) {
		if ( !is_string( $siteId ) ) {
			throw new InvalidArgumentException( '$siteId must be a string.' );
		}

		$this->otherProjectsSidebarGeneratorFactory = $otherProjectsSidebarGeneratorFactory;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->siteId = $siteId;
	}

	/**
	 * Add wikibase_item parser output property
	 *
	 * @param Title $title
	 * @param ParserOutput $out
	 */
	public function updateItemIdProperty( Title $title, ParserOutput $out ) {
		$itemId = $this->getItemIdForTitle( $title );

		if ( $itemId ) {
			$out->setProperty( 'wikibase_item', $itemId->getSerialization() );

			$usageAccumulator = new ParserOutputUsageAccumulator( $out );
			$usageAccumulator->addSiteLinksUsage( $itemId );
		} else {
			$out->unsetProperty( 'wikibase_item' );
		}
	}

	/**
	 * @param Title $title
	 * @param ParserOutput $out
	 */
	public function updateOtherProjectsLinksData( Title $title, ParserOutput $out ) {
		$itemId = $this->getItemIdForTitle( $title );

		if ( $itemId ) {
			$otherProjectsSidebarGenerator = $this->otherProjectsSidebarGeneratorFactory->
				getOtherProjectsSidebarGenerator();

			$otherProjects = $otherProjectsSidebarGenerator->buildProjectLinkSidebar( $title );
			$out->setExtensionData( 'wikibase-otherprojects-sidebar', $otherProjects );
		} else {
			$out->setExtensionData( 'wikibase-otherprojects-sidebar', array() );
		}
	}

	/**
	 * @param Title $title
	 *
	 * @return ItemId|null
	 */
	private function getItemIdForTitle( Title $title ) {
		return $this->siteLinkLookup->getItemIdForLink(
			$this->siteId,
			$title->getFullText()
		);
	}

}
