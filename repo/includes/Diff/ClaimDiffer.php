<?php

namespace Wikibase\Repo\Diff;

use Diff\Differ\Differ;
use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpChange;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;

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

		if ( $oldClaimMainSnak === null && $newClaimMainSnak === null ) {
			return null;
		}

		if( ( $oldClaimMainSnak === null && $newClaimMainSnak !== null )
			|| !$oldClaimMainSnak->equals( $newClaimMainSnak ) ) {
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
		$oldQualifiers = $oldClaim === null ? new SnakList( array() ): $oldClaim->getQualifiers();
		$newQualifiers = $newClaim === null ? new SnakList( array() ) : $newClaim->getQualifiers();

		if ( !$oldQualifiers->equals( $newQualifiers ) ) {
			$diffOps = $this->listDiffer->doDiff(
				iterator_to_array( $oldQualifiers ),
				iterator_to_array( $newQualifiers )
			);

			return new Diff( $diffOps, false );
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

		if( $oldRank !== $newRank ) {
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
		$oldReferences = $oldClaim === null ? new ReferenceList( array() ) : $oldClaim->getReferences();
		$newReferences = $newClaim === null ? new ReferenceList( array() ) : $newClaim->getReferences();

		if ( !$oldReferences->equals( $newReferences ) ) {
			$diffOps = $this->listDiffer->doDiff(
				iterator_to_array( $oldReferences ),
				iterator_to_array( $newReferences )
			);

			return new Diff( $diffOps, false );
		}

		return null;
	}

}
