<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Changes;

use ArrayIterator;
use InvalidArgumentException;
use MediaWiki\Cache\LinkBatchFactory;
use MediaWiki\Page\PageRecord;
use MediaWiki\Page\PageStore;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Title;
use TitleFactory;
use Traversable;
use UnexpectedValueException;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\PageEntityUsages;
use Wikibase\Client\Usage\UsageAspectTransformer;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Changes\Change;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Changes\ItemChange;

/**
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class AffectedPagesFinder {

	/**
	 * @var UsageLookup
	 */
	private $usageLookup;

	/**
	 * @var TitleFactory
	 */
	private $titleFactory;

	/** @var PageStore */
	private $pageStore;

	/** @var LinkBatchFactory */
	private $linkBatchFactory;

	/**
	 * @var string
	 */
	private $siteId;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @param UsageLookup $usageLookup
	 * @param TitleFactory $titleFactory
	 * @param PageStore $pageStore
	 * @param LinkBatchFactory $linkBatchFactory
	 * @param string $siteId
	 * @param LoggerInterface|null $logger
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		UsageLookup $usageLookup,
		TitleFactory $titleFactory,
		PageStore $pageStore,
		LinkBatchFactory $linkBatchFactory,
		string $siteId,
		?LoggerInterface $logger = null
	) {
		$this->usageLookup = $usageLookup;
		$this->titleFactory = $titleFactory;
		$this->pageStore = $pageStore;
		$this->linkBatchFactory = $linkBatchFactory;
		$this->siteId = $siteId;
		$this->logger = $logger ?: new NullLogger();
	}

	/**
	 * @param Change $change
	 *
	 * @return PageEntityUsages[]
	 */
	public function getAffectedUsagesByPage( Change $change ) {
		if ( $change instanceof EntityChange ) {
			$usages = $this->getAffectedPages( $change );
			return $this->filterUpdates( $usages );
		}

		return [];
	}

	/**
	 * @param EntityChange $change
	 *
	 * @return string[]
	 */
	public function getChangedAspects( EntityChange $change ) {
		$aspects = [];
		$diffAspects = $change->getCompactDiff();

		if ( $diffAspects->getSiteLinkChanges() !== [] ) {
			$sitelinkChanges = $diffAspects->getSiteLinkChanges();
			$aspects[] = EntityUsage::SITELINK_USAGE;

			if ( isset( $sitelinkChanges[$this->siteId] )
				&& !$this->isBadgesOnlyChange( $sitelinkChanges[$this->siteId] )
			) {
				$aspects[] = EntityUsage::TITLE_USAGE;
			}
		}

		if ( $diffAspects->getLabelChanges() !== [] ) {
			$labelAspects = $this->getChangedTermAspects(
				EntityUsage::LABEL_USAGE,
				$diffAspects->getLabelChanges()
			);
			$aspects = array_merge( $aspects, $labelAspects );
		}

		if ( $diffAspects->getDescriptionChanges() !== [] ) {
			$descriptionsAspects = $this->getChangedTermAspects(
				EntityUsage::DESCRIPTION_USAGE,
				$diffAspects->getDescriptionChanges()
			);
			$aspects = array_merge( $aspects, $descriptionsAspects );
		}

		if ( $diffAspects->getStatementChanges() !== [] ) {
			$statementAspects = $this->getChangedStatementAspects(
				$diffAspects->getStatementChanges()
			);
			$aspects = array_merge( $aspects, $statementAspects );
		}

		if ( $diffAspects->hasOtherChanges() !== false ) {
			$aspects[] = EntityUsage::OTHER_USAGE;
		}

		if ( $aspects === [] ) {
			// This is needed when diff is suppressed for performance reasons
			$aspects[] = EntityUsage::OTHER_USAGE;
		}

		return $aspects;
	}

	/**
	 * @param string[] $diff
	 *
	 * @return string[]
	 */
	private function getChangedStatementAspects( array $diff ) {
		$aspects = [];

		foreach ( $diff as $propertyId ) {
			$aspects[] = EntityUsage::makeAspectKey( EntityUsage::STATEMENT_USAGE, $propertyId );
		}

		$aspects[] = EntityUsage::makeAspectKey( EntityUsage::STATEMENT_USAGE );

		return $aspects;
	}

	/**
	 * @param string $aspect
	 * @param string[] $diff
	 *
	 * @return string[]
	 */
	private function getChangedTermAspects( $aspect, array $diff ) {
		$aspects = [];

		foreach ( $diff as $lang ) {
			$aspects[] = EntityUsage::makeAspectKey( $aspect, $lang );
		}

		$aspects[] = EntityUsage::makeAspectKey( $aspect );

		return $aspects;
	}

	/**
	 * Returns the page updates implied by the given the change.
	 *
	 * @param EntityChange $change
	 *
	 * @return Traversable of PageEntityUsages
	 *
	 * @see @ref docs_topics_usagetracking for details about virtual usages
	 */
	private function getAffectedPages( EntityChange $change ) {
		$entityId = $change->getEntityId();
		$changedAspects = $this->getChangedAspects( $change );

		$usages = $this->usageLookup->getPagesUsing(
			// @todo: more than one entity at once!
			[ $entityId ],
			// Look up pages that are marked as either using one of the changed or all aspects
			array_merge( $changedAspects, [ EntityUsage::ALL_USAGE ] )
		);

		$usages = $this->transformAllPageEntityUsages( $usages, $entityId, $changedAspects );

		// if title changed, add virtual usages for both old and new title
		if ( $change instanceof ItemChange && in_array( EntityUsage::TITLE_USAGE, $changedAspects ) ) {
			$diffChangedAspects = $change->getCompactDiff();
			$namesFromDiff = $this->getPagesReferencedInDiff(
				$diffChangedAspects->getSiteLinkChanges()
			);
			$titlesFromDiff = $this->getTitlesFromTexts( $namesFromDiff );
			$usagesFromDiff = $this->makeVirtualUsages(
				$titlesFromDiff,
				$entityId,
				[ EntityUsage::SITELINK_USAGE ]
			);

			//FIXME: we can't really merge if $usages is an iterator, not an array.
			//TODO: Inject $usagesFromDiff "on the fly" while streaming other usages.
			//NOTE: $usages must pass through mergeUsagesInto for re-indexing
			$mergedUsages = [];
			$this->mergeUsagesInto( $usages, $mergedUsages );
			$this->mergeUsagesInto( $usagesFromDiff, $mergedUsages );
			$usages = new ArrayIterator( $mergedUsages );
		}

		return $usages;
	}

	/**
	 * @param iterable<PageEntityUsages> $from
	 * @param PageEntityUsages[] &$into Array to merge into
	 */
	private function mergeUsagesInto( iterable $from, array &$into ) {
		foreach ( $from as $pageEntityUsages ) {
			$key = $pageEntityUsages->getPageId();

			if ( isset( $into[$key] ) ) {
				$into[$key]->addUsages( $pageEntityUsages->getUsages() );
			} else {
				$into[$key] = $pageEntityUsages;
			}
		}
	}

	/**
	 * @param array[] $siteLinkDiff
	 *
	 * @throws UnexpectedValueException
	 * @return string[]
	 */
	private function getPagesReferencedInDiff( array $siteLinkDiff ) {
		$pagesToUpdate = [];
		$siteLinkDiffWiki = $siteLinkDiff[$this->siteId];

		if ( $siteLinkDiffWiki[0] !== null ) {
			$pagesToUpdate[] = $siteLinkDiffWiki[0];
		}

		if ( $siteLinkDiffWiki[1] !== null ) {
			$pagesToUpdate[] = $siteLinkDiffWiki[1];
		}

		return $pagesToUpdate;
	}

	/**
	 * @param array $siteLinkDiff
	 *
	 * @return bool
	 */
	private function isBadgesOnlyChange( array $siteLinkDiff ) {
		return ( $siteLinkDiff[0] === $siteLinkDiff[1] && $siteLinkDiff[2] === true );
	}

	/**
	 * Filters updates. This removes duplicates and non-existing pages.
	 *
	 * @param Traversable $usages A traversable of PageEntityUsages.
	 *
	 * @return PageEntityUsages[]
	 */
	private function filterUpdates( Traversable $usages ) {
		$usagesByPageId = [];

		/** @var PageEntityUsages $pageEntityUsages */
		foreach ( $usages as $pageEntityUsages ) {
			$usagesByPageId[$pageEntityUsages->getPageId()] = $pageEntityUsages;
		}

		$titlesToUpdate = [];

		$pageRecords = $this->pageStore
			->newSelectQueryBuilder()
			->wherePageIds( array_keys( $usagesByPageId ) )
			->caller( __METHOD__ )
			->fetchPageRecords();

		/** @var PageRecord $pageRecord */
		foreach ( $pageRecords as $pageRecord ) {
			$pageId = $pageRecord->getId();
			$titlesToUpdate[$pageId] = $usagesByPageId[$pageId];
		}

		return $titlesToUpdate;
	}

	/**
	 * @param string[] $names
	 *
	 * @return Title[]
	 */
	private function getTitlesFromTexts( array $names ) {
		$titles = [];

		foreach ( $names as $name ) {
			$title = $this->titleFactory->newFromText( $name );
			if ( $title ) {
				$titles[] = $title;
			}
		}

		return $titles;
	}

	/**
	 * @param Title[] $titles
	 * @param EntityId $entityId
	 * @param string[] $aspects
	 *
	 * @return PageEntityUsages[]
	 */
	private function makeVirtualUsages( array $titles, EntityId $entityId, array $aspects ) {
		$usagesForItem = [];
		foreach ( $aspects as $aspect ) {
			list( $aspect, $modifier ) = EntityUsage::splitAspectKey( $aspect );
			$usagesForItem[] = new EntityUsage( $entityId, $aspect, $modifier );
		}

		// bulk-load the page IDs into the LinkCache
		$linkBatch = $this->linkBatchFactory->newLinkBatch( $titles );
		$linkBatch->setCaller( __METHOD__ );
		$linkBatch->execute();

		$usagesPerPage = [];
		foreach ( $titles as $title ) {
			$pageId = $title->getArticleID();

			if ( $pageId === 0 ) {
				$this->logger->debug(
					'{method}: Article ID for {titleFullText} is 0',
					[ 'method' => __METHOD__, 'titleFullText' => $title->getFullText() ]
				);

				continue;
			}

			$usagesPerPage[$pageId] = new PageEntityUsages( $pageId, $usagesForItem );
		}

		return $usagesPerPage;
	}

	/**
	 * @param iterable<PageEntityUsages> $usages
	 * @param EntityId $entityId
	 * @param string[] $changedAspects
	 *
	 * @return iterable<PageEntityUsages>
	 */
	private function transformAllPageEntityUsages( iterable $usages, EntityId $entityId, array $changedAspects ): iterable {
		$aspectTransformer = new UsageAspectTransformer();
		$aspectTransformer->setRelevantAspects( $entityId, $changedAspects );

		foreach ( $usages as $key => $usagesOnPage ) {
			$transformedUsagesOnPage = $aspectTransformer->transformPageEntityUsages( $usagesOnPage );

			if ( !$transformedUsagesOnPage->isEmpty() ) {
				yield $key => $transformedUsagesOnPage;
			}
		}
	}

}
