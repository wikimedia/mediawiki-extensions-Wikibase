<?php

namespace Wikibase\Client\Hooks;

use SiteStore;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class OtherProjectsSidebarGeneratorFactory {

	/**
	 * @var OtherProjectsSidebarGenerator
	 */
	private $otherProjectsSidebarGenerator = null;

	/**
	 * @var string
	 */
	private $localSiteId;

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @var SiteStore
	 */
	private $siteStore;

	/**
	 * @var string
	 */
	private $siteIdsToOutput;

	/**
	 * @param string $localSiteId
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param SiteStore $siteStore
	 * @param string[] $siteIdsToOutput
	 */
	public function __construct( $localSiteId, SiteLinkLookup $siteLinkLookup, SiteStore $siteStore,
		array $siteIdsToOutput
	) {
		$this->localSiteId = $localSiteId;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->siteStore = $siteStore;
		$this->siteIdsToOutput = $siteIdsToOutput;
	}

	/**
	 * @return OtherProjectsSidebarGenerator
	 */
	public function getOtherProjectsSidebarGenerator() {
		if ( $this->otherProjectsSidebarGenerator === null ) {
			$this->otherProjectsSidebarGenerator = $this->newOtherProjectsSidebarGenerator();
		}

		return $this->otherProjectsSidebarGenerator;
	}

	/**
	 * @return OtherProjectsSidebarGenerator
	 */
	private function newOtherProjectsSidebarGenerator() {
		return new OtherProjectsSidebarGenerator(
			$this->localSiteId,
			$this->siteLinkLookup,
			$this->siteStore->getSites(),
			$this->siteIdsToOutput
		);
	}

}
