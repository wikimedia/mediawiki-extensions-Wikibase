<?php

namespace Wikibase\Client;

use ParserCache;
use ParserOutput;
use Title;
use Wikibase\Client\Hooks\OtherProjectsSidebarGenerator;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\SiteLinkLookup;
use WikiPage;

/**
 * @licence GNU GPL v2+
 * @author Katie Filbert
 */
class ParserOutputUpdater {

	/**
	 * @var OtherProjectsSidebarGenerator
	 */
	private $otherProjectsSidebarGenerator;

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @var string
	 */
	private $siteId;

	/**
	 * @var ItemId
	 */
	private $itemId;

	/**
	 * @param OtherProjectsSidebarGenerator $otherProjectsSidebarGenerator
	 * @param SiteLinkLookup $siteLinkLookup A site link lookup service
	 * @param string $siteId
	 */
	public function __construct(
		OtherProjectsSidebarGenerator $otherProjectsSidebarGenerator,
		SiteLinkLookup $siteLinkLookup,
		$siteId
	) {
		$this->otherProjectsSidebarGenerator = $otherProjectsSidebarGenerator;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->siteId = $siteId;
	}

	public function updateOtherProjectsSidebar( IContextSource $context, Title $title ) {
		$page = $context->getWikiPage();

		$parserOptions = $page->makeParserOptions( $context );
		$parserOutput = $page->getParserOutput( $parserOptions );

		$this->updateOtherProjectsLinksData( $title, $parserOutput );

		ParserCache::singleton()->save( $parserOutput, $page, $parserOptions );

		return $parserOutput->getExtensionData( 'wikibase-otherprojects-sidebar' );
	}

	/**
	 * @param Title $title
	 * @param ParserOutput $out
	 */
	private function updateOtherProjectsLinksData( Title $title, ParserOutput $out ) {
		$itemId = $this->getItemIdForTitle( $title );

		if ( $itemId ) {
			$otherProjects = $this->otherProjectsSidebarGenerator->buildProjectLinkSidebar( $title );
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
		if ( !isset( $this->itemId ) ) {
			$siteLink = new SiteLink( $this->siteId, $title->getFullText() );
			$this->itemId = $this->siteLinkLookup->getEntityIdForSiteLink( $siteLink );
		}

		return $this->itemId;
	}

}
