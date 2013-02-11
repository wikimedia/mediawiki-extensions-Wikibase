<?php

namespace Wikibase;
use Diff\Diff;
use Diff\DiffOpChange;
use Diff\DiffOpRemove;
use Diff\DiffOpAdd;
use Diff\ListDiffer;
use Diff\MapDiffer;

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

	private $claimDifference;
	
	private $listDiffer;
	
	private $mapDiffer;

	/**
	 * Constructor.
	 *
	 */
	public function __construct() {
		$this->claimDifference = array();
		$this->listDiffer = new ListDiffer();
		$this->mapDiffer = new MapDiffer();
	}

	public function diffClaims( Claim $oldClaim, Claim $newClaim ) {
		$claimDiff = $this->mapDiffer->doDiff( $oldClaim->toArray(), $newClaim->toArray() );

		if ( array_key_exists( 'refs', $claimDiff ) ) {
			$this->claimDifference['type'] = 'refs';
			$diffOp = $this->diffRefList( $oldClaim->getReferences(), $newClaim->getReferences() );
			$this->claimDifference['diff'] = $diffOp;
		} elseif ( array_key_exists( 'rank', $claimDiff ) ) {
			$this->claimDifference['type'] = 'rank';
		} elseif ( array_key_exists( 'm', $claimDiff ) ) {
			$this->claimDifference['type'] = 'm';
			$this->claimDifference['diff'] = new DiffOpChange( $oldClaim, $newClaim );
		} elseif ( array_key_exists( 'q', $claimDiff ) ) {
			$this->claimDifference['type'] = 'q';
		}

		return $this->claimDifference;
	}

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
			print("diffop unknown");
			die();
		}
	}
}
