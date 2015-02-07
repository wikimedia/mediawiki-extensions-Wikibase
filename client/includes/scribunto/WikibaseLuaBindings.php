<?php

namespace Wikibase\Client\Scribunto;

use InvalidArgumentException;
use OutOfBoundsException;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\LabelLookup;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\SettingsArray;

/**
 * Actual implementations of various functions to access Wikibase functionality
 * through Scribunto.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
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
	private $siteLinkTable;

	/**
	 * @var SettingsArray
	 */
	private $settings;

	/**
	 * @var string
	 */
	private $siteId;

	/**
	 * @var LabelLookup
	 */
	private $labelLookup;

	/**
	 * @var UsageAccumulator
	 */
	private $usageAccumulator;

	/**
	 * @param EntityIdParser $entityIdParser
	 * @param EntityLookup $entityLookup
	 * @param SiteLinkLookup $siteLinkTable
	 * @param SettingsArray $settings
	 * @param LabelLookup $labelLookup
	 * @param UsageAccumulator $usageAccumulator
	 * @param string $siteId
	 */
	public function __construct(
		EntityIdParser $entityIdParser,
		EntityLookup $entityLookup,
		SiteLinkLookup $siteLinkTable,
		SettingsArray $settings,
		LabelLookup $labelLookup,
		UsageAccumulator $usageAccumulator,
		$siteId
	) {
		$this->entityIdParser = $entityIdParser;
		$this->entityLookup = $entityLookup;
		$this->siteLinkTable = $siteLinkTable;
		$this->settings = $settings;
		$this->labelLookup = $labelLookup;
		$this->siteId = $siteId;
		$this->usageAccumulator = $usageAccumulator;
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
		$id = $this->siteLinkTable->getItemIdForLink( $this->siteId, $pageTitle );

		if ( !$id ) {
			return null;
		}

		$this->usageAccumulator->addTitleUsage( $id );
		return $id->getSerialization();
	}

	/**
	 * Get global site ID (e.g. "enwiki")
	 * This is basically a helper function.
	 * I can see this becoming part of mw.site in the Scribunto extension.
	 *
	 * @since 0.5
	 *
	 * @return string
	 */
	public function getGlobalSiteId() {
		return $this->siteId;
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
	 * @return string Empty string if entity couldn't be found/ no label present
	 */
	public function getLabel( $prefixedEntityId ) {
		try {
			$entityId = $this->entityIdParser->parse( $prefixedEntityId );
		} catch( EntityIdParsingException $e ) {
			return null;
		}

		try {
			$term = $this->labelLookup->getLabel( $entityId );
		} catch ( StorageException $ex ) {
			return null;
		} catch ( OutOfBoundsException $ex ) {
			return null;
		}

		// NOTE: This tracks a label usage in the wiki's content language.
		//       If the actual label is derived via language fallback,
		//       updates to the source language will not be seen to apply
		//       to this usage. We would need to trigger on changes to
		//       *all* languages to fix that.
		$this->usageAccumulator->addLabelUsage( $entityId );
		return $term->getText();
	}

	/**
	 * @param string $prefixedEntityId
	 *
	 * @since 0.5
	 * @return string Empty string if entity couldn't be found/ no sitelink present
	 */
	public function getSiteLinkPageName( $prefixedEntityId ) {
		try {
			$itemId = new ItemId( $prefixedEntityId );
		} catch( InvalidArgumentException $e ) {
			return null;
		}

		/** @var Item $item */
		$item = $this->entityLookup->getEntity( $itemId );
		if ( !$item || !$item->getSiteLinkList()->hasLinkWithSiteId( $this->siteId ) ) {
			return null;
		}

		$this->usageAccumulator->addTitleUsage( $itemId );
		return $item->getSiteLinkList()->getBySiteId( $this->siteId )->getPageName();
	}

}
