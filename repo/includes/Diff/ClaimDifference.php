<?php

namespace Wikibase\Repo\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpChange;

/**
 * Represents the difference between two Statement objects.
 * @fixme Contains references and rank? It's a StatementDifference!
 *
 * @license GPL-2.0-or-later
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Thiemo Kreuz
 */
class ClaimDifference {

	/**
	 * @var DiffOpChange|null
	 */
	private $mainSnakChange;

	/**
	 * @var Diff|null
	 */
	private $qualifierChanges;

	/**
	 * @var Diff|null
	 */
	private $referenceChanges;

	/**
	 * @var DiffOpChange|null
	 */
	private $rankChange;

	/**
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
		$this->mainSnakChange = $mainSnakChange;
		$this->qualifierChanges = $qualifierChanges;
		$this->referenceChanges = $referenceChanges;
		$this->rankChange = $rankChange;
	}

	/**
	 * @return Diff
	 */
	public function getReferenceChanges() {
		return $this->referenceChanges ?: new Diff( [], false );
	}

	/**
	 * @return DiffOpChange|null
	 */
	public function getMainSnakChange() {
		return $this->mainSnakChange;
	}

	/**
	 * @return DiffOpChange|null
	 */
	public function getRankChange() {
		return $this->rankChange;
	}

	/**
	 * @return Diff
	 */
	public function getQualifierChanges() {
		return $this->qualifierChanges ?: new Diff( [], false );
	}

	/**
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
			&& $this->getQualifierChanges()->equals( $target->getQualifierChanges() )
			&& $this->getReferenceChanges()->equals( $target->getReferenceChanges() )
			&& $this->rankChange == $target->rankChange;
	}

	/**
	 * Checks whether the difference represented by this object is atomic, which means
	 * the Statement has only changed either its main snak, qualifiers, references or rank.
	 *
	 * @return bool
	 */
	public function isAtomic() {
		$aspects = 0;

		if ( $this->mainSnakChange !== null ) {
			$aspects++;
		}
		if ( !$this->getQualifierChanges()->isEmpty() ) {
			$aspects++;
		}
		if ( !$this->getReferenceChanges()->isEmpty() ) {
			$aspects++;
		}
		if ( $this->rankChange !== null ) {
			$aspects++;
		}

		return $aspects === 1;
	}

}
