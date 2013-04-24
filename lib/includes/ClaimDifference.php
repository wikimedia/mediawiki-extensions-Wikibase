<?php

namespace Wikibase;

use Diff\DiffOpChange;
use Diff\Diff;
use Comparable;

/**
 * Represents the difference between two Claim objects.
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
class ClaimDifference implements Comparable {

	/**
	 * @since 0.4
	 *
	 * @var Diff|null
	 */
	private $referenceChanges;

	/**
	 * @since 0.4
	 *
	 * @var DiffOpChange|null
	 */
	private $mainSnakChange;

	/**
	 * @since 0.4
	 *
	 * @var DiffOpChange|null
	 */
	private $rankChange;

	/**
	 * @since 0.4
	 *
	 * @var Diff|null
	 */
	private $qualifierChanges;

	/**
	 * @since 0.4
	 *
	 * @param DiffOpChange|null $mainSnakChange
	 * @param Diff|null $qualifierChanges
	 * @param Diff|null $referenceChanges
	 * @param DiffOpChange|null $rankChange
	 */
	public function __construct( DiffOpChange $mainSnakChange = null, Diff $qualifierChanges = null,
								 Diff $referenceChanges = null, DiffOpChange $rankChange = null ) {

		$this->referenceChanges = $referenceChanges;
		$this->mainSnakChange = $mainSnakChange;
		$this->rankChange = $rankChange;
		$this->qualifierChanges = $qualifierChanges;
	}

	/**
	 * Returns the reference change.
	 *
	 * @since 0.4
	 *
	 * @return Diff
	 */
	public function getReferenceChanges() {
		return $this->referenceChanges === null ? new Diff( array(), false ) : $this->referenceChanges;
	}

	/**
	 * Returns the mainsnak change.
	 *
	 * @since 0.4
	 *
	 * @return DiffOpChange|null
	 */
	public function getMainSnakChange() {
		return $this->mainSnakChange;
	}

	/**
	 * Returns the rank change.
	 *
	 * @since 0.4
	 *
	 * @return DiffOpChange|null
	 */
	public function getRankChange() {
		return $this->rankChange;
	}

	/**
	 * Returns the qualifier change.
	 *
	 * @since 0.4
	 *
	 * @return Diff
	 */
	public function getQualifierChanges() {
		return $this->qualifierChanges === null ? new Diff( array(), false ) : $this->qualifierChanges;
	}

	/**
	 * @see Comparable::equals
	 *
	 * @since 0.1
	 *
	 * @param mixed $target
	 *
	 * @return boolean
	 */
	public function equals( $target ) {
		if ( !( $target instanceof ClaimDifference ) ) {
			return false;
		}

		return $this->getMainSnakChange() == $target->getMainSnakChange()
			&& $this->getRankChange() == $target->getRankChange()
			&& $this->getQualifierChanges() == $target->getQualifierChanges()
			&& $this->getReferenceChanges() == $target->getReferenceChanges();
	}

	/**
	 * Checks whether the ClaimDifference is atomic, which means
	 * the Claim has only changed either its MainSnak, Qualifiers, References or Rank
	 *
	 * @since 0.4
	 *
	 * @return boolean
	 */
	public function isAtomic() {
		$claimChanges = 0;

		if ( $this->getMainSnakChange() !== null ) {
			$claimChanges++;
		}
		if ( $this->getRankChange() !== null ) {
			$claimChanges++;
		}
		if ( !$this->getQualifierChanges()->isEmpty() ) {
			$claimChanges++;
		}
		if ( !$this->getReferenceChanges()->isEmpty() ) {
			$claimChanges++;
		}

		return $claimChanges === 1;
	}

}
