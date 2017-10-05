<?php

namespace Wikibase\Client\DataAccess\Scribunto;

use InvalidArgumentException;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\SettingsArray;

/**
 * Actual implementations of various functions to access Wikibase functionality
 * through Scribunto.
 * All functions in here are independent from the target language, meaning that
 * this class can be instantiated without knowing the target language.
 *
 * @license GPL-2.0+
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 * @author Marius Hoch < hoo@online.de >
 */
class WikibaseLanguageIndependentLuaBindings {

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @var SettingsArray
	 */
	private $settings;

	/**
	 * @var UsageAccumulator
	 */
	private $usageAccumulator;

	/**
	 * @var string
	 */
	private $siteId;

	/**
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param SettingsArray $settings
	 * @param UsageAccumulator $usageAccumulator for tracking title usage via getEntityId.
	 * @param string $siteId
	 */
	public function __construct(
		SiteLinkLookup $siteLinkLookup,
		SettingsArray $settings,
		UsageAccumulator $usageAccumulator,
		$siteId
	) {
		$this->siteLinkLookup = $siteLinkLookup;
		$this->settings = $settings;
		$this->usageAccumulator = $usageAccumulator;
		$this->siteId = $siteId;
	}

	/**
	 * Get entity id from page title.
	 *
	 * @param string $pageTitle
	 *
	 * @return string|null
	 */
	public function getEntityId( $pageTitle ) {
		$id = $this->siteLinkLookup->getItemIdForLink( $this->siteId, $pageTitle );

		if ( !$id ) {
			return null;
		}

		$this->usageAccumulator->addTitleUsage( $id );
		return $id->getSerialization();
	}

	/**
	 * @param string $setting
	 *
	 * @return mixed
	 */
	public function getSetting( $setting ) {
		return $this->settings->getSetting( $setting );
	}

	/**
	 * @param string $prefixedItemId
	 * @param string|null $globalSiteId
	 *
	 * @return string|null Null if no site link found.
	 */
	public function getSiteLinkPageName( $prefixedItemId, $globalSiteId ) {
		$globalSiteId = $globalSiteId ?: $this->siteId;

		try {
			$itemId = new ItemId( $prefixedItemId );
		} catch ( InvalidArgumentException $e ) {
			return null;
		}

		if ( $globalSiteId === $this->siteId ) {
			$this->usageAccumulator->addTitleUsage( $itemId );
		} else {
			$this->usageAccumulator->addSiteLinksUsage( $itemId );
		}

		$siteLinkRows = $this->siteLinkLookup->getLinks(
			[ $itemId->getNumericId() ],
			[ $globalSiteId ]
		);

		foreach ( $siteLinkRows as $siteLinkRow ) {
			$siteLink = new SiteLink( $siteLinkRow[0], $siteLinkRow[1] );
			return $siteLink->getPageName();
		}

		return null;
	}

}
