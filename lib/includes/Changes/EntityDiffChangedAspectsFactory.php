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
 * @license GPL-2.0+
 * @author Marius Hoch
 */
class EntityDiffChangedAspectsFactory {

	public function getFromEntityDiff( Diff $entityDiff ) {
		$labelChanges = [];
		$descriptionChanges = [];
		$statementChanges = [];
		$siteLinkChanges = [];
		$otherChanges = false;

		$remainingDiffOps = count( $entityDiff ); // this is a "deep" count!

		if ( $entityDiff instanceof ItemDiff && !$entityDiff->getSiteLinkDiff()->isEmpty() ) {
			$siteLinkDiff = $entityDiff->getSiteLinkDiff();

			if ( !empty( $siteLinkDiff ) ) {
				$remainingDiffOps -= count( $siteLinkDiff );
				$siteLinkChanges = $this->getChangedSiteLinks( $siteLinkDiff );
			}
		}

		if ( $entityDiff instanceof EntityDiff ) {
			$labelsDiff = $entityDiff->getLabelsDiff();

			if ( !empty( $labelsDiff ) ) {
				$remainingDiffOps -= count( $labelsDiff );
				$labelChanges = $this->getChangedLabels( $labelsDiff );
			}

			$claimsDiff = $entityDiff->getClaimsDiff();
			if ( !empty( $claimsDiff ) ) {
				$remainingDiffOps -= count( $claimsDiff );
				$statementChanges = $this->getChangedStatements( $claimsDiff );
			}

			$descriptionsDiff = $entityDiff->getDescriptionsDiff();
			if ( !empty( $descriptionsDiff ) ) {
				$remainingDiffOps -= count( $descriptionsDiff );
				$descriptionChanges = $this->getChangedDescriptions( $descriptionsDiff );
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
		$labelChanges = [];

		foreach ( $labelsDiff as $lang => $diffOp ) {
			$labelChanges[] = $lang;
		}

		return $labelChanges;
	}

	/**
	 * @param Diff $descriptionsDiff
	 *
	 * @return string[]
	 */
	private function getChangedDescriptions( Diff $descriptionsDiff ) {
		$descriptionChanges = [];

		foreach ( $descriptionsDiff as $lang => $diffOp ) {
			$descriptionChanges[] = $lang;
		}

		return $descriptionChanges;
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
