<?php

namespace Wikibase;

use Diff\Diff;
use Diff\ListDiffer;
use Diff\DiffOpChange;

/**
 * Class for generating a ClaimDifference given two claims.
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
	 */
	public function diffClaims( Claim $oldClaim, Claim $newClaim ) {
		$mainSnakChange = null;
		$rankChange = null;
		$referenceChanges = null;

		if ( !$oldClaim->getMainSnak()->equals( $newClaim->getMainSnak() ) ) {
			$mainSnakChange = new DiffOpChange( $oldClaim->getMainSnak(), $newClaim->getMainSnak() );
		}

		$this->listDiffer->setComparisonCallback( function( \Comparable $old, \Comparable $new ) {
			return $old->equals( $new );
		} );

		$qualifierChanges = new Diff( $this->listDiffer->doDiff(
			iterator_to_array( $oldClaim->getQualifiers() ),
			iterator_to_array( $newClaim->getQualifiers() )
		), false );

		if ( $oldClaim instanceof Statement && $newClaim instanceof Statement ) {

			if ( $oldClaim->getRank() !== $newClaim->getRank() ) {
				$rankChange = new DiffOpChange( $oldClaim->getRank(), $newClaim->getRank() );
			}

			$referenceChanges = new Diff( $this->listDiffer->doDiff(
				iterator_to_array( $oldClaim->getReferences() ),
				iterator_to_array( $newClaim->getReferences() )
			), false );
		}

		return new ClaimDifference( $mainSnakChange, $qualifierChanges, $referenceChanges, $rankChange );
	}

}
