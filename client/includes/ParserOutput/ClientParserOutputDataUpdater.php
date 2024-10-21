<?php

declare( strict_types = 1 );

namespace Wikibase\Client\ParserOutput;

use InvalidArgumentException;
use MediaWiki\Content\Content;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Wikibase\Client\Hooks\OtherProjectsSidebarGeneratorFactory;
use Wikibase\Client\Usage\UsageAccumulatorFactory;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * Update Wikibase ParserOutput properties and extension data.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ClientParserOutputDataUpdater {

	/**
	 * @var OtherProjectsSidebarGeneratorFactory
	 */
	private $otherProjectsSidebarGeneratorFactory;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @var UsageAccumulatorFactory
	 */
	private $usageAccumulatorFactory;

	/**
	 * @var string
	 */
	private $siteId;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @param OtherProjectsSidebarGeneratorFactory $otherProjectsSidebarGeneratorFactory
	 *            Use the factory here to defer initialization of things like Site objects.
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param EntityLookup $entityLookup
	 * @param UsageAccumulatorFactory $usageAccumulatorFactory
	 * @param string $siteId The global site ID for the local wiki
	 * @param LoggerInterface|null $logger
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		OtherProjectsSidebarGeneratorFactory $otherProjectsSidebarGeneratorFactory,
		SiteLinkLookup $siteLinkLookup,
		EntityLookup $entityLookup,
		UsageAccumulatorFactory $usageAccumulatorFactory,
		string $siteId,
		LoggerInterface $logger = null
	) {
		$this->otherProjectsSidebarGeneratorFactory = $otherProjectsSidebarGeneratorFactory;
		$this->entityLookup = $entityLookup;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->usageAccumulatorFactory = $usageAccumulatorFactory;
		$this->siteId = $siteId;
		$this->logger = $logger ?: new NullLogger();
	}

	/**
	 * Add wikibase_item parser output property
	 */
	public function updateItemIdProperty( Title $title, ParserOutputProvider $parserOutputProvider ): void {
		$itemId = $this->getItemIdForTitle( $title );

		if ( $itemId ) {
			$parserOutputProvider->getParserOutput()->setUnsortedPageProperty( 'wikibase_item', $itemId->getSerialization() );

			$usageAccumulator = $this->usageAccumulatorFactory->newFromParserOutputProvider( $parserOutputProvider );
			$usageAccumulator->addSiteLinksUsage( $itemId );
		} else {
			$parserOutputProvider->getParserOutput()->unsetPageProperty( 'wikibase_item' );
		}
	}

	/**
	 * Add tracking category if the page is a redirect and is connected to an item
	 */
	public function updateTrackingCategories( Title $title, ParserOutputProvider $parserOutputProvider ): void {
		$itemId = $this->getItemIdForTitle( $title );

		if ( $itemId && $title->isRedirect() ) {
			$trackingCategories = MediaWikiServices::getInstance()->getTrackingCategories();
			$trackingCategories->addTrackingCategory(
				$parserOutputProvider->getParserOutput(), 'connected-redirect-category', $title
			);
		}
	}

	public function updateOtherProjectsLinksData( Title $title, ParserOutputProvider $parserOutputProvider ): void {
		$itemId = $this->getItemIdForTitle( $title );

		if ( $itemId ) {
			$usageAccumulator = $this->usageAccumulatorFactory->newFromParserOutputProvider( $parserOutputProvider );
			$otherProjectsSidebarGenerator = $this->otherProjectsSidebarGeneratorFactory
				->getOtherProjectsSidebarGenerator( $usageAccumulator );
			$otherProjects = $otherProjectsSidebarGenerator->buildProjectLinkSidebar( $title );
			$parserOutputProvider->getParserOutput()->setExtensionData(
				'wikibase-otherprojects-sidebar',
				$otherProjects
			);
		} else {
			$parserOutputProvider->getParserOutput()->setExtensionData( 'wikibase-otherprojects-sidebar', [] );
		}
	}

	/**
	 * Writes the "unexpectedUnconnectedPage" page property if this page is not linked to an item and
	 * doesn't have the "__EXPECTED_UNCONNECTED_PAGE__" magic word on it.
	 */
	public function updateUnconnectedPageProperty(
		Content $content,
		Title $title,
		ParserOutputProvider $parserOutputProvider ): void {
		$itemId = $this->getItemIdForTitle( $title );

		if ( $itemId || $content->isRedirect() ) {
			// Page is either connected or a redirect (thus expected to be unconnected).
			return;
		}

		$pageProperties = $parserOutputProvider->getParserOutput()->getPageProperties();

		/*
		 * the page prop value is the *negative* namespace,
		 * so that ORDER BY pp_sortkey DESC, page_id DESC orders by ascending namespace and descending page ID,
		 * i.e. Special:UnconnectedPages shows newest main-namespace pages first
		 */
		$value = -$title->getNamespace();
		if ( !isset( $pageProperties['expectedUnconnectedPage'] ) ) {
			$parserOutputProvider->getParserOutput()->setNumericPageProperty( 'unexpectedUnconnectedPage', $value );
		}
	}

	public function updateBadgesProperty( Title $title, ParserOutputProvider $parserOutputProvider ): void {
		$itemId = $this->getItemIdForTitle( $title );

		// first reset all badges in case one got removed
		foreach ( $parserOutputProvider->getParserOutput()->getPageProperties() as $name => $property ) {
			if ( strpos( $name, 'wikibase-badge-' ) === 0 ) {
				$parserOutputProvider->getParserOutput()->unsetPageProperty( $name );
			}
		}

		if ( $itemId ) {
			$this->setBadgesProperty( $itemId, $parserOutputProvider );
		}
	}

	private function setBadgesProperty( ItemId $itemId, ParserOutputProvider $parserOutputProvider ): void {
		/** @var Item $item */
		$item = $this->entityLookup->getEntity( $itemId );
		'@phan-var Item|null $item';

		if ( !$item || !$item->getSiteLinkList()->hasLinkWithSiteId( $this->siteId ) ) {
			// Probably some sort of race condition or data inconsistency.
			// See T183993.
			$this->logger->warning(
				'According to SiteLinkLookup {item} is linked to {site}, but the link does not exist.',
				[
					'item' => $itemId->getSerialization(),
					'site' => $this->siteId,
				]
			);

			return;
		}

		$siteLink = $item->getSiteLinkList()->getBySiteId( $this->siteId );

		foreach ( $siteLink->getBadges() as $badge ) {
			$parserOutputProvider->getParserOutput()->setUnsortedPageProperty(
				'wikibase-badge-' . $badge->getSerialization()
			);
		}
	}

	private function getItemIdForTitle( Title $title ): ?ItemId {
		return $this->siteLinkLookup->getItemIdForLink(
			$this->siteId,
			$title->getPrefixedText()
		);
	}

}
