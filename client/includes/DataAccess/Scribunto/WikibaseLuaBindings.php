<?php

namespace Wikibase\Client\DataAccess\Scribunto;

use InvalidArgumentException;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\SettingsArray;

/**
 * Actual implementations of various functions to access Wikibase functionality
 * through Scribunto.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 * @author Marius Hoch < hoo@online.de >
 */
class WikibaseLuaBindings {

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @var SettingsArray
	 */
	private $settings;

	/**
	 * @var LabelDescriptionLookup
	 */
	private $labelDescriptionLookup;

	/**
	 * @var UsageAccumulator
	 */
	private $usageAccumulator;

	/**
	 * @var string
	 */
	private $siteId;

	/**
	 * @param EntityIdParser $entityIdParser
	 * @param EntityLookup $entityLookup
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param SettingsArray $settings
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 * @param UsageAccumulator $usageAccumulator for tracking title usage via getEntityId.
	 * @param string $siteId
	 *
	 * @note: label usage is not tracked in $usageAccumulator. This should be done inside
	 *        the $labelDescriptionLookup or an underlying TermsLookup.
	 */
	public function __construct(
		EntityIdParser $entityIdParser,
		EntityLookup $entityLookup,
		SiteLinkLookup $siteLinkLookup,
		SettingsArray $settings,
		LabelDescriptionLookup $labelDescriptionLookup,
		UsageAccumulator $usageAccumulator,
		$siteId
	) {
		$this->entityIdParser = $entityIdParser;
		$this->entityLookup = $entityLookup;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->settings = $settings;
		$this->labelDescriptionLookup = $labelDescriptionLookup;
		$this->usageAccumulator = $usageAccumulator;
		$this->siteId = $siteId;
	}

	/**
	 * Get entity id from page title.
	 *
	 * @since 0.5
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
	 * @since 0.5
	 * @return string|null Null if entity couldn't be found/ no label present
	 */
	public function getLabel( $prefixedEntityId ) {
		try {
			$entityId = $this->entityIdParser->parse( $prefixedEntityId );
		} catch ( EntityIdParsingException $e ) {
			return null;
		}

		try {
			$term = $this->labelDescriptionLookup->getLabel( $entityId );
		} catch ( StorageException $ex ) {
			// TODO: verify this catch is still needed
			return null;
		} catch ( LabelDescriptionLookupException $ex ) {
			return null;
		}

		if ( $term === null ) {
			return null;
		}

		// NOTE: This tracks a label usage in the wiki's content language.
		return $term->getText();
	}

	/**
	 * @param string $prefixedEntityId
	 *
	 * @since 0.5
	 * @return string|null Null if entity couldn't be found/ no description present
	 */
	public function getDescription( $prefixedEntityId ) {
		try {
			$entityId = $this->entityIdParser->parse( $prefixedEntityId );
		} catch ( EntityIdParsingException $e ) {
			return null;
		}

		try {
			$term = $this->labelDescriptionLookup->getDescription( $entityId );
		} catch ( StorageException $ex ) {
			// TODO: verify this catch is still needed
			return null;
		} catch ( LabelDescriptionLookupException $ex ) {
			return null;
		}

		if ( $term === null ) {
			return null;
		}

		// XXX: This. Sucks. A lot.
		// Also notes about language fallbacks from getLabel apply
		$this->usageAccumulator->addOtherUsage( $entityId );
		return $term->getText();
	}

	/**
	 * @param string $prefixedEntityId
	 *
	 * @since 0.5
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
			array( $itemId->getNumericId() ),
			array( $this->siteId )
		);

		foreach ( $siteLinkRows as $siteLinkRow ) {
			$siteLink = new SiteLink( $siteLinkRow[0], $siteLinkRow[1] );

			$this->usageAccumulator->addTitleUsage( $itemId );
			return $siteLink->getPageName();
		}

		return null;
	}

}
