<?php

namespace Wikibase\DataModel\Claim;

use Diff\Differ\MapDiffer;
use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use UnexpectedValueException;

/**
 * @since 1.0
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ClaimListDiffer {

	/**
	 * @since 1.0
	 *
	 * @param Claims $fromClaims
	 * @param Claims $toClaims
	 *
	 * @return Diff
	 * @throws UnexpectedValueException
	 */
	public function getDiff( Claims $fromClaims, Claims $toClaims ) {
		$differ = new MapDiffer();

		$hashDifferences = $differ->doDiff(
			$fromClaims->getHashes(),
			$toClaims->getHashes()
		);

		$diff = new Diff( array(), true );

		foreach ( $hashDifferences as $guid => $diffOp ) {
			$diff[$guid] = $this->getDiffOp( $diffOp, $guid, $toClaims, $fromClaims );
		}

		return $diff;
	}

	private function getDiffOp( DiffOp $diffOp, $guid, Claims $toClaims, Claims $fromClaims ) {
		if ( $diffOp instanceof DiffOpChange ) {
			$oldClaim = $fromClaims->getClaimWithGuid( $guid );
			$newClaim = $toClaims->getClaimWithGuid( $guid );

			if ( !( $oldClaim instanceof Claim
					&& $newClaim instanceof Claim
					&& $oldClaim->getGuid() === $newClaim->getGuid() ) ) {
				throw new UnexpectedValueException( 'Invalid operands' );
			}

			return new DiffOpChange( $oldClaim, $newClaim );
		}
		elseif ( $diffOp instanceof DiffOpAdd ) {
			$claim = $toClaims->getClaimWithGuid( $guid );
			return new DiffOpAdd( $claim );
		}
		elseif ( $diffOp instanceof DiffOpRemove ) {
			$claim = $fromClaims->getClaimWithGuid( $guid );
			return new DiffOpRemove( $claim );
		}
		else {
			throw new UnexpectedValueException( 'Invalid DiffOp type cannot be handled' );
		}
	}

}
