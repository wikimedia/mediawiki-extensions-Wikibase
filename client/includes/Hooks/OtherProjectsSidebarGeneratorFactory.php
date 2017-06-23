<?php

namespace Wikibase\Client\Hooks;

use SiteLookup;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\SettingsArray;

/**
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class OtherProjectsSidebarGeneratorFactory {

	/**
	 * @var OtherProjectsSidebarGenerator|null
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
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var SidebarLinkBadgeDisplay
	 */
	private $sidebarLinkBadgeDisplay;

	public function __construct(
		SettingsArray $settings,
		SiteLinkLookup $siteLinkLookup,
		SiteLookup $siteLookup,
		EntityLookup $entityLookup,
		SidebarLinkBadgeDisplay $sidebarLinkBadgeDisplay
	) {
		$this->settings = $settings;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->siteLookup = $siteLookup;
		$this->entityLookup = $entityLookup;
		$this->sidebarLinkBadgeDisplay = $sidebarLinkBadgeDisplay;
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
			$this->siteLookup,
			$this->entityLookup,
			$this->sidebarLinkBadgeDisplay,
			$this->settings->getSetting( 'otherProjectsLinks' )
		);
	}

}
