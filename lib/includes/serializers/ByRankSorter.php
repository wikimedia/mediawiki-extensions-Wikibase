<?php

namespace Wikibase\Lib\Serializers;
use Wikibase\Claim;
use Wikibase\Statement;

/**
 * Sorts claims according to their rank (and property).
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ByRankSorter {

	/**
	 * Sorts claims according to their rank.
	 * The association of array keys with their respective values are maintained.
	 * This uses a stable bubble sort, so the original order of claims with the
	 * same rank is not changed.
	 *
	 * @param Claim[] $claims
	 *
	 * @return Claim[]
	 */
	public function sort( $claims ) {
		$keys = array_keys( $claims );

		foreach ( $keys as $i => $keyI ) {
			/* @var Claim $claim */
			$claimI = $claims[$keyI];

			// bubble up
			for ( $j = $i-1; $j >= 0; $j-- ) {
				$keyJ =  $keys[$j];
				$claimJ =  $claims[$keyJ];

				$comp = $this->compare( $claimI, $claimJ );

				if ( $comp > 0 ) {
					$keys[$j+1] = $keyJ;
					$keys[$j] = $keyI;
				} else {
					break;
				}
			}
		}

		$sorted = array();

		foreach ( $keys as $key ) {
			$sorted[$key] = $claims[$key];
		}

		return $sorted;
	}

	/**
	 * Returns a positive number if $a's rank is larger than $b's,
	 * and a negative number if $b's rank is larger than $a's.
	 *
	 * @param Claim $a
	 * @param Claim $b
	 *
	 * @return Claim|Statement
	 */
	protected function compare( Claim $a, Claim $b ) {
		$rankA = ( $a instanceof Statement ) ? $a->getRank() : Claim::RANK_TRUTH;
		$rankB = ( $b instanceof Statement ) ? $b->getRank() : Claim::RANK_TRUTH;

		return $rankA - $rankB;
	}


}
