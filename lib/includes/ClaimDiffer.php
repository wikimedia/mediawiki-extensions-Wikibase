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
		$mainSnakChange = null;
		$qualifierChanges = null;
		$rankChange = null;
		$referenceChanges = null;

		$oldClaimMainSnak = $oldClaim === null ? null : $oldClaim->getMainSnak();
		$newClaimMainSnak = $newClaim === null ? null : $newClaim->getMainSnak();
		if( $oldClaimMainSnak !== $newClaimMainSnak ){
			$mainSnakChange = new DiffOpChange( $oldClaimMainSnak, $newClaimMainSnak );
		}


		$oldQualifiers = $oldClaim === null ? array() : iterator_to_array( $oldClaim->getQualifiers() );
		$newQualifiers = $newClaim === null ? array() : iterator_to_array( $newClaim->getQualifiers() );
		if( $oldQualifiers !== $newQualifiers ){
			$qualifierChanges = new Diff( $this->listDiffer->doDiff( $oldQualifiers, $newQualifiers ), false );
		}

		if ( $oldClaim instanceof Statement || $newClaim instanceof Statement ) {
			$oldRank = $oldClaim === null ? null : $oldClaim->getRank();
			$newRank = $newClaim === null ? null : $newClaim->getRank();
			if( $oldRank !== $newRank ){
				$rankChange = new DiffOpChange( $oldRank, $newRank );
			}

			$oldReferences = $oldClaim === null ? array() : iterator_to_array( $oldClaim->getReferences() );
			$newReferences = $newClaim === null ? array() : iterator_to_array( $newClaim->getReferences() );
			if( $oldReferences !== $newReferences ){
				$referenceChanges = new Diff( $this->listDiffer->doDiff( $oldReferences, $newReferences ), false );
			}
		}

		return new ClaimDifference( $mainSnakChange, $qualifierChanges, $referenceChanges, $rankChange );
	}

}
