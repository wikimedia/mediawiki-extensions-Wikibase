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
	 * @var ClaimDifference
	 */
	private $claimDifference;

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
	 * @param ClaimDifference $claimDifference
	 * @param ListDiffer $listDiffer
	 */
	public function __construct( ClaimDifference $claimDifference, ListDiffer $listDiffer ) {
		$this->claimDifference = $claimDifference;
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
	 */
	public function diffClaims( Claim $oldClaim, Claim $newClaim ) {
		if ( !$oldClaim->getMainSnak()->equals( $newClaim->getMainSnak() ) ) {
			$this->claimDifference->setMainsnakChange(
				$this->diffMainsnak( $oldClaim->getMainSnak(), $newClaim->getMainSnak() )
			);
		} elseif ( $oldClaim->getRank() !==  $newClaim->getRank() ) {
			$this->claimDifference->setRankChange(
				$this->diffRank( $oldClaim->getRank(), $newClaim->getRank() )
			);
		} elseif ( !$oldClaim->getReferences()->equals( $newClaim->getReferences() ) ) {
			$this->claimDifference->setReferencesChange(
				$this->diffRefList( $oldClaim->getReferences(), $newClaim->getReferences() )
			);
		} elseif ( !$oldClaim->getQualifiers()->equals( $newClaim->getQualifiers() ) ) {
			$this->claimDifference->setQualifiersChange(
				$this->diffQualifiers( $oldClaim->getQualifiers(), $newClaim->getQualifiers() )
			);
		}

		return $this->claimDifference;
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
	 */
	private function diffRefList( ReferenceList $oldRefList, ReferenceList $newRefList ) {
		$refListDiff = $this->listDiffer->doDiff( $oldRefList->toArray(), $newRefList->toArray() );

		$diffOp = $refListDiff[0];
		if ( $diffOp->getType() === 'add' ) {
			$snakList = SnakList::newFromArray( $diffOp->getNewValue() );
			return new DiffOpAdd( $snakList );
		} elseif ( $diffOp->getType() === 'remove' ) {
			$snakList = SnakList::newFromArray( $diffOp->getOldValue() );
			return new DiffOpRemove( $snakList );
		} elseif ( $diffOp->getType() === 'change' ) {
			$oldSnakList = SnakList::newFromArray( $diffOp->getOldValue() );
			$newSnakList = SnakList::newFromArray( $diffOp->getNewValue() );
			return new DiffOpChange( $oldSnakList, $newSnakList );
		} else {
			throw new MWException( 'diffop unknown' );
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
