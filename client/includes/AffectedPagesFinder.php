<?php

namespace Wikibase;

use ArrayIterator;
use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use InvalidArgumentException;
use Iterator;
use Title;
use UnexpectedValueException;
use Wikibase\Client\Store\TitleFactory;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\PageEntityUsages;
use Wikibase\Client\Usage\UsageAspectTransformer;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\DataModel\Entity\Diff\EntityDiff;
use Wikibase\DataModel\Entity\Diff\ItemDiff;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\StorageException;

/**
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class AffectedPagesFinder {

	/**
	 * @var UsageLookup
	 */
	private $usageLookup;

	/**
	 * @var NamespaceChecker
	 */
	private $namespaceChecker;

	/**
	 * @var string
	 */
	private $siteId;

	/**
	 * @var string
	 */
	private $contentLanguageCode;

	/**
	 * @var boolean
	 */
	private $checkPageExistence;

	/**
	 * @var TitleFactory
	 */
	private $titleFactory;

	/**
	 * @param UsageLookup $usageLookup
	 * @param NamespaceChecker $namespaceChecker
	 * @param TitleFactory $titleFactory
	 * @param string $siteId
	 * @param string $contentLanguageCode
	 * @param boolean $checkPageExistence
	 */
	public function __construct(
		UsageLookup $usageLookup,
		NamespaceChecker $namespaceChecker,
		TitleFactory $titleFactory,
		$siteId,
		$contentLanguageCode,
		$checkPageExistence = true
	) {
		if ( !is_string( $siteId ) ) {
			throw new InvalidArgumentException( '$siteId must be a string' );
		}

		if ( !is_string( $contentLanguageCode ) ) {
			throw new InvalidArgumentException( '$contentLanguageCode must be a string' );
		}

		if ( !is_bool( $checkPageExistence ) ) {
			throw new InvalidArgumentException( '$checkPageExistence must be a boolean' );
		}

		$this->usageLookup = $usageLookup;
		$this->namespaceChecker = $namespaceChecker;
		$this->siteId = $siteId;
		$this->contentLanguageCode = $contentLanguageCode;
		$this->checkPageExistence = $checkPageExistence;
		$this->titleFactory = $titleFactory;
	}

	/**
	 * @since 0.5
	 *
	 * @param Change $change
	 *
	 * @return Iterator<PageEntityUsages>
	 */
	public function getPagesToUpdate( EntityChange $change ) {
		if ( ! ( $change instanceof ItemChange ) ) {
			return array();
		}

		$pageUpdates = $this->getAffectedPages( $change );
		$pageUpdates = $this->filterUpdates( $pageUpdates );

		return $pageUpdates;
	}

	/**
	 * @param EntityChange $change
	 *
	 * @return string[]
	 */
	public function getChangedAspects( EntityChange $change ) {
		$aspects = array();

		/** @var EntityDiff $diff */
		$diff = $change->getDiff();
		$remainingDiffOps = count( $diff ); // this is a "deep" count!

		if ( $diff instanceof ItemDiff && !$diff->getSiteLinkDiff()->isEmpty() ) {
			$sitelinkDiff = $diff->getSiteLinkDiff();

			$aspects[] = EntityUsage::SITELINK_USAGE;
			$remainingDiffOps-= count( $sitelinkDiff );

			if ( isset( $sitelinkDiff[$this->siteId] ) && !$this->isBadgesOnlyChange( $sitelinkDiff[$this->siteId] ) ) {
				$aspects[] = EntityUsage::TITLE_USAGE;
			}
		}

		if ( !$diff->getLabelsDiff()->isEmpty() ) {
			$labelDiff = $diff->getLabelsDiff();

			if ( isset( $labelDiff[$this->contentLanguageCode] ) ) {
				$aspects[] = EntityUsage::LABEL_USAGE;
				$remainingDiffOps--;
			}
		}

		if ( $remainingDiffOps > 0 ) {
			$aspects[] = EntityUsage::OTHER_USAGE;
		}

		sort( $aspects );
		return $aspects;
	}

	/**
	 * Returns the page updates implied by the given the change.
	 *
	 * @param EntityChange $change
	 *
	 * @return Iterator<PageEntityUsages>
	 */
	private function getAffectedPages( EntityChange $change ) {
		$itemId = $change->getEntityId();
		$changedAspects = $this->getChangedAspects( $change );

		// @todo: more than one item at once!
		$relevantAspects = array_merge( array( 'X', $changedAspects ) ); // X implies all!
		$usages = $this->usageLookup->getPagesUsing( array( $itemId ), $relevantAspects );

		// @todo: use iterators throughout!
		$usages = iterator_to_array( $usages );

		$usages = $this->transformAllPageEntityUsages( $usages, $itemId, $changedAspects );

		if ( in_array( EntityUsage::TITLE_USAGE, $changedAspects ) && $change instanceof ItemChange ) {
			$siteLinkDiff = $change->getSiteLinkDiff();
			$namesFromDiff = $this->getPagesReferencedInDiff( $siteLinkDiff );
			$titlesFromDiff = $this->getTitlesFromTexts( $namesFromDiff );
			$usagesFromDiff = $this->makeVirtualUsages( $titlesFromDiff, $itemId, array( EntityUsage::SITELINK_USAGE ) );

			//FIXME: we can't really merge if $usages is an iterator, not an array. We'd have to blindly append.
			$usages = $this->mergeUsageArrays( $usages, $usagesFromDiff );
		}

		return new ArrayIterator( $usages );
	}

	/**
	 * @param PageEntityUsages[] $base
	 * @param PageEntityUsages[] $extra
	 *
	 * @return PageEntityUsages[]
	 */
	private function mergeUsageArrays( array $base, array $extra ) {
		$merged = array();

		/** @var PageEntityUsages $pageEntityUsages */
		foreach ( array_merge( $base, $extra ) as $pageEntityUsages ) {
			$key = $pageEntityUsages->getPageId();

			if ( isset( $merged[$key] ) ) {
				$merged[$key]->addUsages( $pageEntityUsages->getUsages() );
			} else {
				$merged[$key] = $pageEntityUsages;
			}
		}

		return $merged;
	}

	/**
	 * @param Diff $siteLinkDiff
	 *
	 * @throws UnexpectedValueException
	 * @return array
	 */
	private function getPagesReferencedInDiff( Diff $siteLinkDiff ) {
		$pagesToUpdate = array();

		// $siteLinkDiff changed from containing atomic diffs to
		// containing map diffs. For B/C, handle both cases.
		$siteLinkDiffOp = $siteLinkDiff[$this->siteId];

		if ( ( $siteLinkDiffOp instanceof Diff ) && ( array_key_exists( 'name', $siteLinkDiffOp ) ) ) {
			$siteLinkDiffOp = $siteLinkDiffOp['name'];
		}

		if ( $siteLinkDiffOp instanceof DiffOpAdd ) {
			$pagesToUpdate[] = $siteLinkDiffOp->getNewValue();
		} elseif ( $siteLinkDiffOp instanceof DiffOpRemove ) {
			$pagesToUpdate[] = $siteLinkDiffOp->getOldValue();
		} elseif ( $siteLinkDiffOp instanceof DiffOpChange ) {
			$pagesToUpdate[] = $siteLinkDiffOp->getNewValue();
			$pagesToUpdate[] = $siteLinkDiffOp->getOldValue();
		} else {
			throw new UnexpectedValueException(
				"Unknown change operation: " . get_class( $siteLinkDiffOp ) . ")"
			);
		}

		return $pagesToUpdate;
	}

	/**
	 * @param DiffOp $siteLinkDiffOp
	 *
	 * @return boolean
	 */
	private function isBadgesOnlyChange( DiffOp $siteLinkDiffOp ) {

		return ( $siteLinkDiffOp instanceof Diff && !array_key_exists( 'name', $siteLinkDiffOp ) );
	}

	/**
	 * Filters updates based on namespace. This removes duplicates, non-existing pages, and pages from
	 * namespaces that are not considered "enabled" by the namespace checker.
	 *
	 * @param PageEntityUsages[]|Iterator<PageEntityUsages> $updates
	 *
	 * @return Iterator<PageEntityUsages>
	 */
	private function filterUpdates( $updates ) {
		$titlesToUpdate = array();

		foreach ( $updates as $pageUpdates ) {
			$title = $this->titleFactory->newFromID( $pageUpdates->getPageId() );

			if ( $this->checkPageExistence && !$title->exists() ) {
				continue;
			}

			$ns = $title->getNamespace();

			if ( !$this->namespaceChecker->isWikibaseEnabled( $ns ) ) {
				continue;
			}

			$key = $title->getArticleID();
			$titlesToUpdate[$key] = $pageUpdates;
		}

		return new ArrayIterator( $titlesToUpdate );
	}

	/**
	 * @param string[] $names
	 *
	 * @return Title[]
	 */
	private function getTitlesFromTexts( $names ) {
		$titles = array();

		foreach ( $names as $name ) {
			try {
				$titles[] = $this->titleFactory->newFromText( $name );
			} catch ( StorageException $ex ) {
				// Invalid title in the diff? Skip.
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
		$usagesForItem = array();
		foreach ( $aspects as $aspect ) {
			$usagesForItem[] = new EntityUsage( $entityId, $aspect );
		}

		$usagesPerPage = array();
		foreach ( $titles as $title ) {
			$pid = $title->getArticleID();
			$usagesPerPage[$pid] = new PageEntityUsages( $pid, $usagesForItem );
		}

		return $usagesPerPage;
	}

	private function transformAllPageEntityUsages( $usages, EntityId $entityId, array $changedAspects ) {
		$aspectTransformer = new UsageAspectTransformer();
		$aspectTransformer->setRelevantAspects( $entityId, $changedAspects );

		$transformed = array();

		foreach( $usages as $key => $usagesOnPage ) {
			$transformedUsagesOnPage = $aspectTransformer->transformPageEntityUsages( $usagesOnPage );
			if ( (bool)$transformedUsagesOnPage->getUsages() ) {
				$transformed[$key] = $transformedUsagesOnPage;
			}
		}

		return $transformed;
	}

}
