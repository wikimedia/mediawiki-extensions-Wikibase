<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Usage;

use MediaWiki\Cache\LinkBatchFactory;
use TitleFactory;
use Traversable;
use Wikibase\Client\Store\DescriptionLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * A {@link UsageLookup} which decorates an inner lookup
 * and adds an implicit usage on a linked item’s description.
 *
 * An implicit usage is different from an ordinary, explicit usage
 * in that it is never recorded by a {@link UsageTracker}:
 * it is not tracked when the page is parsed and actually uses a part of an entity,
 * but rather synthesized by this class based on hard-coded knowledge
 * about where else an entity’s data is used in relation to a page.
 * However, implicit usages otherwise look exactly like explicit usages:
 * for a user of the {@link UsageLookup} interface, it is not (yet?) possible
 * to determine whether a usage from the lookup is explicit or implicit.
 *
 * This class implements one kind of implicit usage:
 * if a client page is linked to an item, it has an implicit usage
 * on that item’s description in the client wiki’s content language,
 * unless the client page also has a local description overriding the central one.
 * This is because the description is used, for example,
 * as part of the search result for the page (typically on mobile),
 * even if it is never used in the page itself.
 *
 * @see @ref docs_topics_usagetracking for virtual usage,
 * a similar but separate concept.
 *
 * @license GPL-2.0-or-later
 */
class ImplicitDescriptionUsageLookup implements UsageLookup {

	/** @var UsageLookup */
	private $usageLookup;

	/** @var TitleFactory */
	private $titleFactory;

	/** @var bool */
	private $allowLocalShortDesc;

	/** @var DescriptionLookup */
	private $descriptionLookup;

	/** @var LinkBatchFactory */
	private $linkBatchFactory;

	/** @var string */
	private $globalSiteId;

	/** @var SiteLinkLookup */
	private $siteLinkLookup;

	/**
	 * @param UsageLookup $usageLookup The underlying/inner lookup.
	 * @param TitleFactory $titleFactory
	 * @param bool $allowLocalShortDesc The 'allowLocalShortDesc' client setting.
	 * If true, only pages with a local description will get an implicit usage.
	 * @param DescriptionLookup $descriptionLookup Used to look up local descriptions.
	 * Unused if $allowLocalShortDesc is false.
	 * @param LinkBatchFactory $linkBatchFactory
	 * @param string $globalSiteId The global site ID of the client wiki.
	 * @param SiteLinkLookup $siteLinkLookup
	 */
	public function __construct(
		UsageLookup $usageLookup,
		TitleFactory $titleFactory,
		bool $allowLocalShortDesc,
		DescriptionLookup $descriptionLookup,
		LinkBatchFactory $linkBatchFactory,
		string $globalSiteId,
		SiteLinkLookup $siteLinkLookup
	) {
		$this->usageLookup = $usageLookup;
		$this->titleFactory = $titleFactory;
		$this->allowLocalShortDesc = $allowLocalShortDesc;
		$this->descriptionLookup = $descriptionLookup;
		$this->linkBatchFactory = $linkBatchFactory;
		$this->globalSiteId = $globalSiteId;
		$this->siteLinkLookup = $siteLinkLookup;
	}

	public function getUsagesForPage( int $pageId ): array {
		$usages = $this->usageLookup->getUsagesForPage( $pageId );
		$title = $this->titleFactory->newFromID( $pageId );
		if ( !$title ) {
			return $usages;
		}

		if (
			$this->allowLocalShortDesc &&
			$this->descriptionLookup->getDescription( $title, DescriptionLookup::SOURCE_LOCAL )
		) {
			// central short description overridden locally, no implicit usage
			return $usages;
		}

		$entityId = $this->siteLinkLookup->getItemIdForLink(
			$this->globalSiteId,
			$title->getPrefixedText()
		);
		if ( !$entityId ) {
			return $usages;
		}

		$contentLanguage = $title->getPageLanguage()->getCode();

		$usage = new EntityUsage(
			$entityId,
			EntityUsage::DESCRIPTION_USAGE,
			$contentLanguage
		);
		// this might replace an existing usage but that’s okay
		$usages[$usage->getIdentityString()] = $usage;

		return $usages;
	}

	public function getPagesUsing( array $entityIds, array $aspects = [] ): Traversable {
		if ( !$this->aspectsMatchImplicitUsage( $aspects ) ) {
			// Caller is not interested in implicit usage,
			// no need to add anything
			return yield from $this->usageLookup->getPagesUsing( $entityIds, $aspects );
		}

		// Find the implicit usages that we’ll add – one per page / item ID / content language
		[ $itemIdsByPageId, $contentLanguagesByPageId ] = $this->findImplicitUsages( $entityIds );
		// Filter them according to the aspects
		[ $itemIdsByPageId, $contentLanguagesByPageId ] = $this->filterImplicitUsages(
			$itemIdsByPageId, $contentLanguagesByPageId, $aspects );

		// Now decorate the inner lookup’s usages with them
		foreach ( $this->usageLookup->getPagesUsing( $entityIds, $aspects ) as $pageEntityUsages ) {
			/** @var PageEntityUsages $pageEntityUsages */
			'@phan-var PageEntityUsages $pageEntityUsages';
			$pageId = $pageEntityUsages->getPageId();
			if ( isset( $itemIdsByPageId[$pageId] ) ) {
				// if equivalent usages already exist then addUsages() is a no-op
				$pageEntityUsages->addUsages( [
					new EntityUsage(
						$itemIdsByPageId[$pageId],
						EntityUsage::DESCRIPTION_USAGE,
						$contentLanguagesByPageId[$pageId]
					),
				] );
				unset( $itemIdsByPageId[$pageId] );
			}
			yield $pageEntityUsages;
		}

		// And yield any remaining pages that the inner lookup didn’t return at all
		foreach ( $itemIdsByPageId as $pageId => $itemId ) {
			yield new PageEntityUsages( $pageId, [
				new EntityUsage(
					$itemId,
					EntityUsage::DESCRIPTION_USAGE,
					$contentLanguagesByPageId[$pageId]
				),
			] );
		}
	}

	/**
	 * Whether the given aspects potentially match an implicit usage.
	 *
	 * @param string[] $aspects
	 * @return bool
	 */
	private function aspectsMatchImplicitUsage( array $aspects ): bool {
		if ( $aspects === [] ) {
			return true;
		}

		foreach ( $aspects as $aspectKey ) {
			if ( EntityUsage::stripModifier( $aspectKey ) === EntityUsage::DESCRIPTION_USAGE ) {
				// The implicit usage is on the description in the *page* content language,
				// not the wiki content language, so any description aspect matches.
				// (We’ll later filter based on the modifier and content language,
				// see filterImplicitUsages().)
				return true;
			}
		}
		return false;
	}

	/**
	 * Find the implicit usages on the given entity IDs.
	 *
	 * Returns two arrays, both keyed by page ID:
	 * the item ID linked to that page and the content language of the page.
	 *
	 * @param EntityId[] $entityIds
	 * @return array [ ItemId[] $itemIdsByPageId, string[] $contentLanguagesByPageId ]
	 */
	private function findImplicitUsages( array $entityIds ): array {
		$numericItemIds = [];
		foreach ( $entityIds as $entityId ) {
			if ( $entityId instanceof ItemId ) {
				$numericItemIds[] = $entityId->getNumericId();
			}
		}

		// each link is an array [ string $siteId, string $pageName, int $itemId ]
		$links = $this->siteLinkLookup->getLinks( $numericItemIds, [ $this->globalSiteId ] );
		// preload the titles in bulk (page ID and language)
		$titles = array_map( [ $this->titleFactory, 'newFromDBkey' ], array_column( $links, 1 ) );
		$linkBatch = $this->linkBatchFactory->newLinkBatch( $titles );
		$linkBatch->setCaller( __METHOD__ );
		$linkBatch->execute();

		if ( $this->allowLocalShortDesc ) {
			// look up which of them have local descriptions
			$localShortDescriptions = $this->descriptionLookup->getDescriptions(
				$titles,
				DescriptionLookup::SOURCE_LOCAL
			);
			// (any page ID that exists in $localShortDescriptions overrides the central description
			// locally and should therefore not have an implicit usage)
		} else {
			$localShortDescriptions = [];
		}

		$itemIdsByPageId = [];
		foreach ( $links as [ $siteId, $pageName, $itemId ] ) {
			// note: this creates a new Title and looks up its page ID in the link cache;
			// this is simpler than finding the right existing Title in the $titles we have
			// (the $pageName is probably not exactly in DB key form)
			$pageId = $this->titleFactory->newFromDBkey( $pageName )->getArticleID();
			if ( $pageId && !isset( $localShortDescriptions[$pageId] ) ) {
				$itemIdsByPageId[$pageId] = ItemId::newFromNumber( $itemId );
			}
		}

		$contentLanguagesByPageId = [];
		foreach ( $titles as $title ) {
			$pageId = $title->getArticleID();
			if ( $pageId && !isset( $localShortDescriptions[$pageId] ) ) {
				$contentLanguagesByPageId[$pageId] = $title->getPageLanguage()->getCode();
			}
		}

		return [ $itemIdsByPageId, $contentLanguagesByPageId ];
	}

	/**
	 * Filter the implicit usages by the given aspects.
	 *
	 * Takes two arrays as returned by {@link findImplicitUsages}
	 * and returns similar arrays, but filtered if necessary.
	 *
	 * @param ItemId[] $itemIdsByPageId
	 * @param string[] $contentLanguagesByPageId
	 * @param string[] $aspects
	 * @return array [ ItemId[] $itemIdsByPageId, string[] $contentLanguagesByPageId ]
	 */
	private function filterImplicitUsages(
		array $itemIdsByPageId,
		array $contentLanguagesByPageId,
		array $aspects
	): array {
		if ( $aspects === [] ) {
			// caller is interested in all usages, don’t filter
			return [ $itemIdsByPageId, $contentLanguagesByPageId ];
		}

		$relevantLanguages = [];
		foreach ( $aspects as $aspectKey ) {
			[ $aspect, $modifier ] = EntityUsage::splitAspectKey( $aspectKey );
			if ( $aspect !== EntityUsage::DESCRIPTION_USAGE ) {
				continue;
			}
			if ( $modifier === null ) {
				// caller is interested in all description usages, don’t filter
				return [ $itemIdsByPageId, $contentLanguagesByPageId ];
			}
			$relevantLanguages[] = $modifier;
		}

		// caller is only interested in some description usages, filter
		foreach ( $contentLanguagesByPageId as $pageId => $contentLanguage ) {
			if ( !in_array( $contentLanguage, $relevantLanguages, /* strict */ true ) ) {
				unset( $itemIdsByPageId[$pageId] );
				unset( $contentLanguagesByPageId[$pageId] );
			}
		}
		return [ $itemIdsByPageId, $contentLanguagesByPageId ];
	}

	public function getUnusedEntities( array $entityIds ): array {
		// If a page is linked to an item, it has at least a sitelink usage on it;
		// therefore, the implicit usage can never make a difference for
		// whether an entity is used or unused.
		return $this->usageLookup->getUnusedEntities( $entityIds );
	}

}
