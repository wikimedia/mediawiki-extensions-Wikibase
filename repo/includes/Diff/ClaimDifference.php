<?php

namespace Wikibase\Repo\Diff;

use Comparable;
use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpChange;

/**
 * Represents the difference between two Claim objects.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ClaimDifference implements Comparable {

	/**
	 * @var Diff|null
	 */
	private $referenceChanges;

	/**
	 * @var DiffOpChange|null
	 */
	private $mainSnakChange;

	/**
	 * @var DiffOpChange|null
	 */
	private $rankChange;

	/**
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
	public function __construct(
		DiffOpChange $mainSnakChange = null,
		Diff $qualifierChanges = null,
		Diff $referenceChanges = null,
		DiffOpChange $rankChange = null
	) {
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
		return $this->referenceChanges ?: new Diff( array(), false );
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
		return $this->qualifierChanges ?: new Diff( array(), false );
	}

	/**
	 * @see Comparable::equals
	 *
	 * @since 0.1
	 *
	 * @param mixed $target
	 *
	 * @return bool
	 */
	public function equals( $target ) {
		if ( $target === $this ) {
			return true;
		}

		if ( !( $target instanceof self ) ) {
			return false;
		}

		return $this->mainSnakChange == $target->mainSnakChange
			&& $this->rankChange == $target->rankChange
			// FIXME: Use Diff::equals when released.
			&& $this->getQualifierChanges()->getArrayCopy() == $target->getQualifierChanges()->getArrayCopy()
			&& $this->getReferenceChanges()->getArrayCopy() == $target->getReferenceChanges()->getArrayCopy();
	}

	/**
	 * Checks whether the ClaimDifference is atomic, which means
	 * the Claim has only changed either its MainSnak, Qualifiers, References or Rank
	 *
	 * @since 0.4
	 *
	 * @return bool
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
