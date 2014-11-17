<?php

namespace Wikibase\Client\Hooks;

use SiteStore;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\SettingsArray;

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
	 * @var SettingsArray
	 */
	private $settings;

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @var SiteStore
	 */
	private $siteStore;

	/**
	 * @param SettingsArray $settings
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param SiteStore $siteStore
	 */
	public function __construct(
		SettingsArray $settings,
		SiteLinkLookup $siteLinkLookup,
		SiteStore $siteStore
	) {
		$this->settings = $settings;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->siteStore = $siteStore;
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
			$this->settings->getSetting( 'siteGlobalID' ),
			$this->siteLinkLookup,
			$this->siteStore->getSites(),
			$this->settings->getSetting( 'otherProjectsLinks' )
		);
	}

}
