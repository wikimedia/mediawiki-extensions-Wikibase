<?php

namespace Wikibase;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Title;
use UnexpectedValueException;
use Wikibase\Client\Usage\UsageLookup;

/**
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ReferencedPagesFinder {

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
	 * @param UsageLookup $usageLookup
	 * @param NamespaceChecker $namespaceChecker
	 * @param string $siteId
	 * @param boolean $checkPageExistence
	 */
	public function __construct(
		UsageLookup $usageLookup,
		NamespaceChecker $namespaceChecker,
		$siteId,
		$checkPageExistence = true
	) {
		$this->usageLookup = $usageLookup;
		$this->namespaceChecker = $namespaceChecker;
		$this->siteId = $siteId;
		$this->checkPageExistence = $checkPageExistence;
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

		$pages = $this->getReferencedPages( $change );

		return $this->getTitlesToUpdate( $pages );
	}

	/**
	 * Returns the pages that need some kind of updating given the change.
	 *
	 * @param ItemChange $change
	 *
	 * @return Title[] the titles of the pages to update
	 */
	private function getReferencedPages( ItemChange $change ) {
		$itemId = $change->getEntityId();

		// TODO: filter by aspect!
		// TODO: handle very large lists of pages nicely
		$pages = $this->usageLookup->getPagesUsing( array( $itemId ) );
		$pages = iterator_to_array( $pages );

		$siteLinkDiff = $change->getSiteLinkDiff();

		if ( $this->isRelevantSiteLinkChange( $siteLinkDiff ) ) {
			$pages = $this->addSiteLinkDiffPages( $siteLinkDiff, $pages );
		}

		return array_unique( $pages );
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
	 * @param array $pages
	 *
	 * @return string[]
	 */
	private function addSiteLinkDiffPages( Diff $siteLinkDiff, $pages ) {
		return array_merge(
			$pages,
			$this->getPagesReferencedInDiff( $siteLinkDiff )
		);
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
	 * @param array $pagesToUpdate
	 *
	 * @return Title[]
	 */
	private function getTitlesToUpdate( array $pagesToUpdate ) {
		$titlesToUpdate = array();

		foreach ( $pagesToUpdate as $page ) {
			$title = Title::newFromText( $page );

			if ( $this->checkPageExistence && !$title->exists() ) {
				continue;
			}

			$ns = $title->getNamespace();

			if ( !$this->namespaceChecker->isWikibaseEnabled( $ns ) ) {
				continue;
			}

			$titlesToUpdate[] = $title;
		}

		return $titlesToUpdate;
	}

}
