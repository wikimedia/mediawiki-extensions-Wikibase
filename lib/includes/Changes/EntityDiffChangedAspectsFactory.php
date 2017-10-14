<?php

namespace Wikibase\Lib\Changes;

use Diff\DiffOp\DiffOp;
use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Services\Diff\ItemDiff;

/**
 * Factory for EntityDiffChangedAspects.
 *
 * @license GPL-2.0+
 * @author Marius Hoch
 */
class EntityDiffChangedAspectsFactory {

	/**
	 * Get an EntityDiffChangedAspects instance from an EntityDiff.
	 *
	 * @param Diff $entityDiff
	 * @return EntityDiffChangedAspects
	 */
	public function newFromEntityDiff( Diff $entityDiff ) {
		$labelChanges = [];
		$descriptionChanges = [];
		$statementChanges = [];
		$siteLinkChanges = [];
		$otherChanges = false;

		$remainingDiffOps = count( $entityDiff ); // this is a "deep" count!

		if ( $entityDiff instanceof ItemDiff && !$entityDiff->getSiteLinkDiff()->isEmpty() ) {
			$siteLinkDiff = $entityDiff->getSiteLinkDiff();

			$remainingDiffOps -= count( $siteLinkDiff );
			$siteLinkChanges = $this->getChangedSiteLinks( $siteLinkDiff );
		}

		if ( $entityDiff instanceof EntityDiff ) {
			$labelsDiff = $entityDiff->getLabelsDiff();
			if ( !empty( $labelsDiff ) ) {
				$remainingDiffOps -= count( $labelsDiff );
				$labelChanges = $this->getChangedLabels( $labelsDiff );
			}

			$descriptionsDiff = $entityDiff->getDescriptionsDiff();
			if ( !empty( $descriptionsDiff ) ) {
				$remainingDiffOps -= count( $descriptionsDiff );
				$descriptionChanges = $this->getChangedDescriptions( $descriptionsDiff );
			}

			$claimsDiff = $entityDiff->getClaimsDiff();
			if ( !empty( $claimsDiff ) ) {
				$remainingDiffOps -= count( $claimsDiff );
				$statementChanges = $this->getChangedStatements( $claimsDiff );
			}
		}

		if ( $remainingDiffOps > 0 ) {
			$otherChanges = true;
		}

		return new EntityDiffChangedAspects(
			$labelChanges,
			$descriptionChanges,
			$statementChanges,
			$siteLinkChanges,
			$otherChanges
		);
	}

	/**
	 * @param Diff $siteLinkDiff
	 *
	 * @return string[]
	 */
	private function getChangedSiteLinks( Diff $siteLinkDiff ) {
		$siteLinkChanges = [];

		foreach ( $siteLinkDiff as $siteId => $diffPerSite ) {
			$siteLinkChanges[$siteId] = !$this->isBadgesOnlyChange( $diffPerSite );
		}

		return $siteLinkChanges;
	}

	/**
	 * @param DiffOp $siteLinkDiffOp
	 *
	 * @return bool
	 */
	private function isBadgesOnlyChange( DiffOp $siteLinkDiffOp ) {
		return $siteLinkDiffOp instanceof Diff && !array_key_exists( 'name', $siteLinkDiffOp );
	}

	/**
	 * @param Diff $labelsDiff
	 *
	 * @return string[]
	 */
	private function getChangedLabels( Diff $labelsDiff ) {
		return array_keys( iterator_to_array( $labelsDiff ) );
	}

	/**
	 * @param Diff $descriptionsDiff
	 *
	 * @return string[]
	 */
	private function getChangedDescriptions( Diff $descriptionsDiff ) {
		return array_keys( iterator_to_array( $descriptionsDiff ) );
	}

	/**
	 * @param Diff $claimsDiff
	 *
	 * @return string[]
	 */
	private function getChangedStatements( Diff $claimsDiff ) {
		$changedStatements = [];

		foreach ( $claimsDiff as $pid => $diffOp ) {
			/* @var $statement Statement */

			if ( $diffOp instanceof DiffOpAdd ) {
				$statement = $diffOp->getNewValue();
			} elseif ( $diffOp instanceof DiffOpRemove ) {
				$statement = $diffOp->getOldValue();
			} elseif ( $diffOp instanceof DiffOpChange ) {
				$statement = $diffOp->getOldValue();
				/* @var $newStatement Statement */
				$newStatement = $diffOp->getNewValue();

				$changedStatements[] = $newStatement->getPropertyId()->getSerialization();
			} else {
				wfLogWarning( 'Unknown DiffOp type ' . get_class( $diffOp ) );
			}

			$changedStatements[] = $statement->getPropertyId()->getSerialization();
		}

		return array_unique( $changedStatements );
	}

}
