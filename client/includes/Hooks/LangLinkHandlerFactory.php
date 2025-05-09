<?php

namespace Wikibase\Client\Hooks;

use MediaWiki\Site\SiteLookup;
use Psr\Log\LoggerInterface;
use Wikibase\Client\NamespaceChecker;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class LangLinkHandlerFactory {

	/**
	 * @var LanguageLinkBadgeDisplay
	 */
	private $badgeDisplay;

	/**
	 * @var NamespaceChecker
	 */
	private $namespaceChecker;

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * @var WikibaseClientSiteLinksForItemHook
	 */
	private $hookRunner;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var string
	 */
	private $siteId;

	/**
	 * @var string[]
	 */
	private $siteGroups;

	/**
	 * @param LanguageLinkBadgeDisplay $badgeDisplay
	 * @param NamespaceChecker $namespaceChecker determines which namespaces wikibase is enabled on
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param EntityLookup $entityLookup
	 * @param SiteLookup $siteLookup
	 * @param WikibaseClientHookRunner $hookRunner
	 * @param LoggerInterface $logger
	 * @param string $siteId The global site ID for the local wiki
	 * @param string[] $siteGroups The ID of the site group to use for showing language links.
	 */
	public function __construct(
		LanguageLinkBadgeDisplay $badgeDisplay,
		NamespaceChecker $namespaceChecker,
		SiteLinkLookup $siteLinkLookup,
		EntityLookup $entityLookup,
		SiteLookup $siteLookup,
		WikibaseClientHookRunner $hookRunner,
		LoggerInterface $logger,
		string $siteId,
		array $siteGroups
	) {
		$this->badgeDisplay = $badgeDisplay;
		$this->namespaceChecker = $namespaceChecker;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->entityLookup = $entityLookup;
		$this->siteLookup = $siteLookup;
		$this->hookRunner = $hookRunner;
		$this->logger = $logger;
		$this->siteId = $siteId;
		$this->siteGroups = $siteGroups;
	}

	public function getLangLinkHandler( UsageAccumulator $usageAccumulator ): LangLinkHandler {
		return new LangLinkHandler(
			$this->badgeDisplay,
			$this->namespaceChecker,
			new SiteLinksForDisplayLookup(
				$this->siteLinkLookup,
				$this->entityLookup,
				$usageAccumulator,
				$this->hookRunner,
				$this->logger,
				$this->siteId
			),
			$this->siteLookup,
			$this->siteId,
			$this->siteGroups
		);
	}
}
