<?php

namespace Wikibase;

use Diff\DiffOp\Diff\Diff;
use Diff\Differ\Differ;
use Diff\DiffOp\DiffOpChange;
use Wikibase\DataModel\Internal\ObjectComparer;

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

	private $listDiffer;

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
	 * @param Claim|null $oldClaim
	 * @param Claim|null $newClaim
	 *
	 * @return DiffOpChange|null
	 */
	private function diffMainSnaks( Claim $oldClaim = null, Claim $newClaim = null ) {
		$oldClaimMainSnak = $oldClaim === null ? null : $oldClaim->getMainSnak();
		$newClaimMainSnak = $newClaim === null ? null : $newClaim->getMainSnak();

		$mainSnakComparer = new ObjectComparer();
		if( !$mainSnakComparer->dataEquals( $oldClaimMainSnak, $newClaimMainSnak ) ) {
			return new DiffOpChange( $oldClaimMainSnak, $newClaimMainSnak );
		}
		return null;
	}

	/**
	 * @param Claim|null $oldClaim
	 * @param Claim|null $newClaim
	 *
	 * @return Diff
	 */
	private function diffQualifiers( Claim $oldClaim = null, Claim $newClaim = null ) {
		$oldQualifiers = $oldClaim === null ? array() : iterator_to_array( $oldClaim->getQualifiers() );
		$newQualifiers = $newClaim === null ? array() : iterator_to_array( $newClaim->getQualifiers() );

		$qualifierComparer = new ObjectComparer();
		if (  !$qualifierComparer->dataEquals( $oldQualifiers, $newQualifiers ) ) {
			return new Diff( $this->listDiffer->doDiff( $oldQualifiers, $newQualifiers ), false );
		}
		return null;
	}

	/**
	 * @param Statement|null $oldClaim
	 * @param Statement|null $newClaim
	 *
	 * @return DiffOpChange|null
	 */
	private function diffRank( Statement $oldClaim = null, Statement $newClaim = null ) {
		$oldRank = $oldClaim === null ? null : $oldClaim->getRank();
		$newRank = $newClaim === null ? null : $newClaim->getRank();

		$rankComparer = new ObjectComparer();
		if( !$rankComparer->dataEquals( $oldRank, $newRank ) ){
			return new DiffOpChange( $oldRank, $newRank );
		}
		return null;
	}

	/**
	 * @param Statement|null $oldClaim
	 * @param Statement|null $newClaim
	 *
	 * @return Diff
	 */
	private function diffReferences( Statement $oldClaim = null, Statement $newClaim = null ) {
		$oldReferences = $oldClaim === null ? array() : iterator_to_array( $oldClaim->getReferences() );
		$newReferences = $newClaim === null ? array() : iterator_to_array( $newClaim->getReferences() );

		$referenceComparer = new ObjectComparer();
		if ( !$referenceComparer->dataEquals( $oldReferences, $newReferences ) ) {
			return new Diff( $this->listDiffer->doDiff( $oldReferences, $newReferences ), false );
		}
		return null;
	}

}
