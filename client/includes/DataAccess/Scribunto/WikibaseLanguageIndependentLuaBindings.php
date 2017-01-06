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
	 * @param string $prefixedEntityId
	 *
	 * @return string|null Null if no site link found.
	 */
	public function getSiteLinkPageName( $prefixedEntityId ) {
		try {
			$itemId = new ItemId( $prefixedEntityId );
		} catch ( InvalidArgumentException $e ) {
			return null;
		}

		// @fixme the SiteLinks do not contain badges! but all we want here is page name.
		$siteLinkRows = $this->siteLinkLookup->getLinks(
			[ $itemId->getNumericId() ],
			[ $this->siteId ]
		);

		foreach ( $siteLinkRows as $siteLinkRow ) {
			$siteLink = new SiteLink( $siteLinkRow[0], $siteLinkRow[1] );

			$this->usageAccumulator->addTitleUsage( $itemId );
			return $siteLink->getPageName();
		}

		return null;
	}

}
