<?php

namespace Wikibase\Client\Hooks;

use SiteLookup;
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
	 * @param SettingsArray $settings
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param SiteLookup $siteLookup
	 */
	public function __construct(
		SettingsArray $settings,
		SiteLinkLookup $siteLinkLookup,
		SiteLookup $siteLookup
	) {
		$this->settings = $settings;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->siteLookup = $siteLookup;
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
			$this->settings->getSetting( 'otherProjectsLinks' )
		);
	}

}
