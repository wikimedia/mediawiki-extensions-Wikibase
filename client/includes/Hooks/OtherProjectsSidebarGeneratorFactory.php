<?php

namespace Wikibase\Client\Hooks;

use MediaWiki\HookContainer\HookContainer;
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
	 * @var HookContainer
	 */
	private $hooksContainer;

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
		HookContainer $hookContainer,
		LoggerInterface $logger
	) {
		$this->settings = $settings;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->siteLookup = $siteLookup;
		$this->entityLookup = $entityLookup;
		$this->sidebarLinkBadgeDisplay = $sidebarLinkBadgeDisplay;
		$this->hooksContainer = $hookContainer;
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
			new SiteLinksForDisplayLookup(
				$this->siteLinkLookup,
				$this->entityLookup,
				$usageAccumulator,
				$this->hooksContainer,
				$this->logger,
				$this->settings->getSetting( 'siteGlobalID' )
			),
			$this->siteLookup,
			$this->sidebarLinkBadgeDisplay,
			$this->settings->getSetting( 'otherProjectsLinks' )
		);
	}

}
