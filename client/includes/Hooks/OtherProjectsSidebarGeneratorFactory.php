<?php

namespace Wikibase\Client\Hooks;

use Psr\Log\LoggerInterface;
use SiteLookup;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class OtherProjectsSidebarGeneratorFactory {

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

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	public function __construct(
		SettingsArray $settings,
		SiteLinkLookup $siteLinkLookup,
		SiteLookup $siteLookup,
		EntityLookup $entityLookup,
		SidebarLinkBadgeDisplay $sidebarLinkBadgeDisplay,
		LoggerInterface $logger
	) {
		$this->settings = $settings;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->siteLookup = $siteLookup;
		$this->entityLookup = $entityLookup;
		$this->sidebarLinkBadgeDisplay = $sidebarLinkBadgeDisplay;
		$this->logger = $logger;
	}

	/**
	 * @param UsageAccumulator $usageAccumulator
	 *
	 * @return OtherProjectsSidebarGenerator
	 */
	public function getOtherProjectsSidebarGenerator( UsageAccumulator $usageAccumulator ) {
		return new OtherProjectsSidebarGenerator(
			$this->settings->getSetting( 'siteGlobalID' ),
			$this->siteLinkLookup,
			new SiteLinksForDisplayLookup(
				$this->siteLinkLookup,
				$this->entityLookup,
				$this->logger,
				$this->settings->getSetting( 'siteGlobalID' )
			),
			$this->siteLookup,
			$this->sidebarLinkBadgeDisplay,
			$usageAccumulator,
			$this->settings->getSetting( 'otherProjectsLinks' )
		);
	}

}
