<?php

namespace Wikibase;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Iterator;
use Title;
use UnexpectedValueException;
use Wikibase\Client\Store\TitleFactory;
use Wikibase\Client\Usage\UsageLookup;
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
	 * @param boolean $checkPageExistence
	 */
	public function __construct(
		UsageLookup $usageLookup,
		NamespaceChecker $namespaceChecker,
		TitleFactory $titleFactory,
		$siteId,
		$checkPageExistence = true
	) {
		$this->usageLookup = $usageLookup;
		$this->namespaceChecker = $namespaceChecker;
		$this->siteId = $siteId;
		$this->checkPageExistence = $checkPageExistence;
		$this->titleFactory = $titleFactory;
	}

	/**
	 * @since 0.5
	 *
	 * @param Change $change
	 *
	 * @return Title[]
	 */
	public function getPages( Change $change ) {
		if ( ! ( $change instanceof ItemChange ) ) {
			return array();
		}

		$titles = $this->getReferencedPages( $change );

		return $this->filterTitlesToUpdate( $titles );
	}

	/**
	 * Returns the pages that need some kind of updating given the change.
	 *
	 * @param ItemChange $change
	 *
	 * @return Title[] the titles of the pages to update. May contain duplicates.
	 */
	private function getReferencedPages( ItemChange $change ) {
		$itemId = $change->getEntityId();

		$pageIds = $this->usageLookup->getPagesUsing( array( $itemId ) );
		$titles = $this->getTitlesFromIDs( $pageIds );

		$siteLinkDiff = $change->getSiteLinkDiff();

		if ( $this->isRelevantSiteLinkChange( $siteLinkDiff ) ) {
			$namesFromDiff = $this->getPagesReferencedInDiff( $siteLinkDiff );
			$titlesFromDiff = $this->getTitlesFromTexts( $namesFromDiff );

			$titles = array_merge( $titles, $titlesFromDiff );
		}

		return $titles;
	}

	/**
	 * @param Diff $siteLinkDiff
	 *
	 * @return boolean
	 */
	private function isRelevantSiteLinkChange( Diff $siteLinkDiff ) {
		return isset( $siteLinkDiff[$this->siteId] ) && !$this->isBadgesOnlyChange( $siteLinkDiff );
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
	 * @param Diff $siteLinkDiff
	 *
	 * @return boolean
	 */
	private function isBadgesOnlyChange( Diff $siteLinkDiff ) {
		$siteLinkDiffOp = $siteLinkDiff[$this->siteId];

		return ( $siteLinkDiffOp instanceof Diff && !array_key_exists( 'name', $siteLinkDiffOp ) );
	}

	/**
	 * Filters titles to update. This removes duplicates, non-existing pages, and pages from
	 * namespaces that are not considered "enabled" by the namespace checker.
	 *
	 * @param Title[] $titles
	 *
	 * @return Title[]
	 */
	private function filterTitlesToUpdate( array $titles ) {
		$titlesToUpdate = array();

		foreach ( $titles as $title ) {
			if ( $this->checkPageExistence && !$title->exists() ) {
				continue;
			}

			$ns = $title->getNamespace();

			if ( !$this->namespaceChecker->isWikibaseEnabled( $ns ) ) {
				continue;
			}

			// Use the string representation as a key to get rid of any duplicates.
			$key = $title->getPrefixedDBkey();
			$titlesToUpdate[$key] = $title;
		}

		return $titlesToUpdate;
	}

	/**
	 * @param int[]|Iterator $pageIds
	 *
	 * @return Title[]
	 */
	private function getTitlesFromIDs( $pageIds ) {
		$titles = array();

		foreach ( $pageIds as $id ) {
			try {
				$titles[] = $this->titleFactory->newFromID( $id );
			} catch ( StorageException $ex ) {
				// Page probably got deleted just now. Skip it.
			}
		}

		return $titles;
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

}
