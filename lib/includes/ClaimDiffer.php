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

		$oldQualifiers = iterator_to_array( $oldClaim->getQualifiers() );
		$newQualifiers = iterator_to_array( $newClaim->getQualifiers() );

		$qualifierChanges = new Diff( $this->listDiffer->doDiff(
			$oldQualifiers,
			$newQualifiers
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
