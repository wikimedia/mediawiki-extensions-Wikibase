<?php

namespace Wikibase;
use Diff\Diff;
use Diff\ListDiff;
use Diff\DiffOpChange;
use Diff\DiffOpRemove;
use Diff\DiffOpAdd;
use Diff\ListDiffer;

/**
 * Class for generating Diffs between two Claims.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */

class ClaimDiffer {

	/**
	 * @since 0.4
	 *
	 * @var ListDiffer
	 */
	private $listDiffer;

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param ListDiffer $listDiffer
	 */
	public function __construct( ListDiffer $listDiffer ) {
		$this->listDiffer = $listDiffer;
	}

	/**
	 * Calculates diff of two Claims and stores the difference in a ClaimDifference
	 *
	 * @since 0.4
	 *
	 * @param Claim $oldClaim
	 * @param Claim $newClaim
	 *
	 * @return ClaimDifference
	 * TODO: for now we assume that just a single part of the claim changed. this might be enough for now, as we
	 * are only showing atomic changes in diff view but it should be extended to be able to show multiple changes in one.
	 */
	public function diffClaims( Claim $oldClaim, Claim $newClaim ) {
		$mainsnakChange = null;
		$referenceChange = null;
		$rankChange = null;
		$qualifierChange = null;

		if ( !$oldClaim->getMainSnak()->equals( $newClaim->getMainSnak() ) ) {
			$mainsnakChange = $this->diffMainsnak( $oldClaim->getMainSnak(), $newClaim->getMainSnak() );
		} elseif ( $oldClaim->getRank() !==  $newClaim->getRank() ) {
			$rankChange = $this->diffRank( $oldClaim->getRank(), $newClaim->getRank() );
		} elseif ( !$oldClaim->getReferences()->equals( $newClaim->getReferences() ) ) {
			$referenceChange = $this->diffRefList( $oldClaim->getReferences(), $newClaim->getReferences() );
		} elseif ( !$oldClaim->getQualifiers()->equals( $newClaim->getQualifiers() ) ) {
			$qualifierChange = $this->diffQualifiers( $oldClaim->getQualifiers(), $newClaim->getQualifiers() );
		}

		return new ClaimDifference( $mainsnakChange, $referenceChange, $rankChange, $qualifierChange );
	}

	/**
	 * Calculates diff of two ReferenceLists
	 *
	 * @since 0.4
	 *
	 * @param ReferenceList $oldRefList
	 * @param ReferenceList $newRefList
	 *
	 * @return DiffOp
	 * @throws \MWException
	 * 
	 * TODO: we are only caring about atomic changes to the references here as we only display atomic changes
	 * in the diff view. because references only have a hash and no distinct ID, a change to a reference
	 * is equivalent to removing the old one and adding a new one (see inline comment in the method). this should
	 * be extended to handle non-atomic changes later.
	 */
	private function diffRefList( ReferenceList $oldRefList, ReferenceList $newRefList ) {
		$refListDiff = $this->listDiffer->doDiff( $oldRefList->toArray(), $newRefList->toArray() );

		if ( count( $refListDiff ) == 1 ) {
			$diffOp = $refListDiff[0];
			if ( $diffOp->getType() === 'add' ) {
				$snakList = SnakList::newFromArray( $diffOp->getNewValue() );
				return new DiffOpAdd( $snakList );
			} elseif ( $diffOp->getType() === 'remove' ) {
				$snakList = SnakList::newFromArray( $diffOp->getOldValue() );
				return new DiffOpRemove( $snakList );
			} else {
				throw new MWException( 'unsupported diffop for reference: ' . $diffOp->getType() );
			}
		} elseif ( count( $refListDiff ) == 2 ) {
			// changing a reference means removing the old version and adding a new one,
			// so there's one add and one remove op
			$diffOpAdd = $refListDiff[0];
			$diffOpRemove = $refListDiff[1];
			$newSnakList = SnakList::newFromArray( $diffOpAdd->getNewValue() );
			$oldSnakList = SnakList::newFromArray( $diffOpRemove->getOldValue() );
			return new DiffOpChange( $oldSnakList, $newSnakList );
		} else {
			throw new MWException( 'not an atomic change!' );
		}
	}

	/**
	 * Calculates diff between Ranks
	 *
	 * @since 0.4
	 *
	 * @param integer $oldRank
	 * @param integer $newRank
	 *
	 * @return DiffOpChange
	 */
	private function diffRank( $oldRank, $newRank ) {
		return new DiffOpChange( $oldRank, $newRank );
	}

	/**
	 * Calculates diff of two Snaks
	 *
	 * @since 0.4
	 *
	 * @param Snak $oldSnak
	 * @param Snak $newSnak
	 *
	 * @return DiffOpChange
	 */
	private function diffMainsnak( Snak $oldSnak, Snak $newSnak ) {
		return new DiffOpChange( $oldSnak, $newSnak );
	}

	/**
	 * Calculates diff between Qualifiers
	 *
	 * @since 0.4
	 *
	 * @param SnakList $oldQualifiers
	 * @param SnakList $newQualifiers
	 *
	 * @return null
	 *
	 * TODO: implement this!
	 */
	private function diffQualifiers( SnakList $oldQualifiers, SnakList $newQualifiers ) {
		return null;
	}
}
