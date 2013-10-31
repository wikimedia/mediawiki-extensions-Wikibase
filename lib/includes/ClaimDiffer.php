<?php

namespace Wikibase;

use Diff\Diff;
use Diff\Differ;
use Diff\DiffOpChange;

/**
 * Class for generating a ClaimDifference given two claims.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Adam Shorland
 */
class ClaimDiffer {

	/**
	 * @since 0.4
	 *
	 * @var Differ
	 */
	private $listDiffer;

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param Differ $listDiffer
	 */
	public function __construct( Differ $listDiffer ) {
		$this->listDiffer = $listDiffer;
	}

	/**
	 * Calculates diff of two Claims and stores the difference in a ClaimDifference
	 *
	 * @since 0.4
	 *
	 * @param Claim|null $oldClaim
	 * @param Claim|null $newClaim
	 *
	 * @return ClaimDifference
	 */
	public function diffClaims( $oldClaim, $newClaim ) {
		$mainSnakChange = $this->diffMainSnaks( $oldClaim, $newClaim );
		$qualifierChanges = $this->diffQualifiers( $oldClaim, $newClaim );

		if ( $oldClaim instanceof Statement || $newClaim instanceof Statement ) {
			$rankChange = $this->diffRank( $oldClaim, $newClaim );
			$referenceChanges = $this->diffReferences( $oldClaim, $newClaim );
		} else {
			$rankChange = null;
			$referenceChanges = null;
		}

		return new ClaimDifference( $mainSnakChange, $qualifierChanges, $referenceChanges, $rankChange );
	}

	/**
	 * @param Claim $oldClaim
	 * @param Claim $newClaim
	 *
	 * @return DiffOpChange|null
	 */
	private function diffMainSnaks( $oldClaim, $newClaim ) {
		$oldClaimMainSnak = $oldClaim === null ? null : $oldClaim->getMainSnak();
		$newClaimMainSnak = $newClaim === null ? null : $newClaim->getMainSnak();
		if( $oldClaimMainSnak !== $newClaimMainSnak ){
			return new DiffOpChange( $oldClaimMainSnak, $newClaimMainSnak );
		}
		return null;
	}

	/**
	 * @param Claim $oldClaim
	 * @param Claim $newClaim
	 *
	 * @return DiffOpChange|null
	 */
	private function diffQualifiers( $oldClaim, $newClaim ) {
		$oldQualifiers = $oldClaim === null ? array() : iterator_to_array( $oldClaim->getQualifiers() );
		$newQualifiers = $newClaim === null ? array() : iterator_to_array( $newClaim->getQualifiers() );
		if( $oldQualifiers !== $newQualifiers ){
			return new Diff( $this->listDiffer->doDiff( $oldQualifiers, $newQualifiers ), false );
		}
		return null;
	}

	/**
	 * @param Statement $oldClaim
	 * @param Statement $newClaim
	 *
	 * @return DiffOpChange|null
	 */
	private function diffRank( $oldClaim, $newClaim ) {
		$oldRank = $oldClaim === null ? null : $oldClaim->getRank();
		$newRank = $newClaim === null ? null : $newClaim->getRank();
		if( $oldRank !== $newRank ){
			return new DiffOpChange( $oldRank, $newRank );
		}
		return null;
	}

	/**
	 * @param Statement $oldClaim
	 * @param Statement $newClaim
	 *
	 * @return DiffOpChange|null
	 */
	private function diffReferences( $oldClaim, $newClaim ) {
		$oldReferences = $oldClaim === null ? array() : iterator_to_array( $oldClaim->getReferences() );
		$newReferences = $newClaim === null ? array() : iterator_to_array( $newClaim->getReferences() );
		if( $oldReferences !== $newReferences ){
			return new Diff( $this->listDiffer->doDiff( $oldReferences, $newReferences ), false );
		}
		return null;
	}

}
