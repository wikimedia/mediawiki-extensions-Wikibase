<?php

namespace Wikibase\Repo\Diff;

use Diff\Differ\ListDiffer;
use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Message;
use RuntimeException;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\Lib\Serializers\ClaimSerializer;

/**
 * Class for generating HTML for Claim Diffs.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Adam Shorland
 * @author Daniel Kinzler
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 */
class ClaimDifferenceVisualizer {

	/**
	 * @var DifferencesSnakVisualizer
	 */
	private $snakVisualizer;

	/**
	 * @var string
	 */
	private $languageCode;

	/**
	 * @since 0.4
	 *
	 * @param DifferencesSnakVisualizer $snakVisualizer
	 * @param string $languageCode
	 */
	public function __construct(
		DifferencesSnakVisualizer $snakVisualizer,
		$languageCode
	) {
		$this->snakVisualizer = $snakVisualizer;
		$this->languageCode = $languageCode;
	}

	/**
	 * Generates HTML of a claim change.
	 * @since 0.4
	 *
	 * @param ClaimDifference $claimDifference
	 * @param Claim $baseClaim The new claim, if it exists; otherwise, the old claim
	 *
	 * @return string
	 */
	public function visualizeClaimChange( ClaimDifference $claimDifference, Claim $baseClaim ) {
		$newestMainSnak = $baseClaim->getMainSnak();
		$oldestMainSnak = $newestMainSnak;
		$html = '';

		$mainSnakChange = $claimDifference->getMainSnakChange();
		if ( $mainSnakChange !== null ) {
			$oldestMainSnak = $mainSnakChange->getOldValue() ?: $newestMainSnak;
			$html .= $this->visualizeMainSnakChange( $mainSnakChange, $oldestMainSnak, $newestMainSnak );
		}

		$rankChange = $claimDifference->getRankChange();
		if ( $rankChange !== null ) {
			$html .= $this->visualizeRankChange(
				$rankChange,
				$oldestMainSnak,
				$newestMainSnak
			);
		}

		$qualifierChanges = $claimDifference->getQualifierChanges();
		if ( $qualifierChanges !== null ) {
			$html .= $this->visualizeQualifierChanges(
				$qualifierChanges,
				$oldestMainSnak,
				$newestMainSnak
			);
		}

		$referenceChanges = $claimDifference->getReferenceChanges();
		if ( $referenceChanges !== null ) {
			$msg = wfMessage( 'wikibase-diffview-reference' )->inLanguage( $this->languageCode );
			$html .= $this->visualizeSnakListChanges(
				$referenceChanges,
				$msg,
				$oldestMainSnak,
				$newestMainSnak
			);
		}

		return $html;
	}

	/**
	 * Get diff html for a new claim
	 *
	 * @since 0.4
	 *
	 * @param Claim $claim
	 *
	 * @return string
	 */
	public function visualizeNewClaim( Claim $claim ) {
		$claimDiffer = new ClaimDiffer( new ListDiffer() );
		$claimDifference = $claimDiffer->diffClaims( null, $claim );
		return $this->visualizeClaimChange( $claimDifference, $claim );
	}

	/**
	 * Get diff html for a removed claim
	 *
	 * @since 0.4
	 *
	 * @param Claim $claim
	 *
	 * @return string
	 */
	public function visualizeRemovedClaim( Claim $claim ) {
		$claimDiffer = new ClaimDiffer( new ListDiffer() );
		$claimDifference = $claimDiffer->diffClaims( $claim, null );
		return $this->visualizeClaimChange( $claimDifference, $claim );
	}

	/**
	 * Get Html for a main snak change
	 *
	 * @since 0.4
	 *
	 * @param DiffOpChange $mainSnakChange
	 * @param Snak $oldestMainSnak The old main snak, if present; otherwise, the new main snak
	 * @param Snak $newestMainSnak The new main snak, if present; otherwise, the old main snak
	 *
	 * @return string
	 */
	private function visualizeMainSnakChange(
		DiffOpChange $mainSnakChange,
		Snak $oldestMainSnak,
		Snak $newestMainSnak
	) {
		$valueFormatter = new DiffOpValueFormatter(
			$this->snakVisualizer->getPropertyHeader( $oldestMainSnak ),
			$this->snakVisualizer->getPropertyHeader( $newestMainSnak ),
			// TODO: How to highlight the actual changes inside the snak?
			$this->snakVisualizer->getDetailedValue( $mainSnakChange->getOldValue() ),
			$this->snakVisualizer->getDetailedValue( $mainSnakChange->getNewValue() )
		);

		return $valueFormatter->generateHtml();
	}

	/**
	 * Get Html for rank change
	 *
	 * @since 0.4
	 *
	 * @param DiffOpChange $rankChange
	 * @param Snak $oldestMainSnak The old main snak, if present; otherwise, the new main snak
	 * @param Snak $newestMainSnak The new main snak, if present; otherwise, the old main snak
	 *
	 * @return string
	 */
	private function visualizeRankChange(
		DiffOpChange $rankChange,
		Snak $oldestMainSnak,
		Snak $newestMainSnak
	) {
		$msg = wfMessage( 'wikibase-diffview-rank' )->inLanguage( $this->languageCode );
		$valueFormatter = new DiffOpValueFormatter(
			$this->snakVisualizer->getPropertyAndValueHeader( $oldestMainSnak ) . ' / ' .
				$msg->parse(),
			$this->snakVisualizer->getPropertyAndValueHeader( $newestMainSnak ) . ' / ' .
				$msg->parse(),
			$this->getRankHtml( $rankChange->getOldValue() ),
			$this->getRankHtml( $rankChange->getNewValue() )
		);

		return $valueFormatter->generateHtml();
	}

	/**
	 * @param string|int|null $rank
	 *
	 * @return string|null HTML
	 */
	private function getRankHtml( $rank ) {
		if ( $rank === null ) {
			return null;
		}

		if ( is_int( $rank ) ) {
			$rank = ClaimSerializer::serializeRank( $rank );
		}

		// Messages: wikibase-diffview-rank-preferred, wikibase-diffview-rank-normal,
		// wikibase-diffview-rank-deprecated
		$msg = wfMessage( 'wikibase-diffview-rank-' . $rank )->inLanguage( $this->languageCode );
		return $msg->parse();
	}

	/**
	 * Get Html for snaklist changes
	 *
	 * @since 0.4
	 *
	 * @param Diff $changes
	 * @param Message $breadCrumb
	 * @param Snak $oldestMainSnak The old main snak, if present; otherwise, the new main snak
	 * @param Snak $newestMainSnak The new main snak, if present; otherwise, the old main snak
	 *
	 * @return string
	 * @throws RuntimeException
	 */
	private function visualizeSnakListChanges(
		Diff $changes,
		Message $breadCrumb,
		Snak $oldestMainSnak,
		Snak $newestMainSnak
	) {
		$html = '';

		$oldClaimHeader = $this->snakVisualizer->getPropertyAndValueHeader( $oldestMainSnak ) . ' / ' .
			$breadCrumb->parse();
		$newClaimHeader = $this->snakVisualizer->getPropertyAndValueHeader( $newestMainSnak ) . ' / ' .
			$breadCrumb->parse();

		foreach ( $changes as $change ) {
			$newVal = $oldVal = null;
			if ( $change instanceof DiffOpAdd || $change instanceof DiffOpChange ) {
				$newVal = $this->mapSnakList(
					$change->getNewValue()->getSnaks(),
					array( $this->snakVisualizer, 'getPropertyAndDetailedValue' )
				);
			}
			if ( $change instanceof DiffOpRemove || $change instanceof DiffOpChange ) {
				$oldVal = $this->mapSnakList(
					$change->getOldValue()->getSnaks(),
					array( $this->snakVisualizer, 'getPropertyAndDetailedValue' )
				);
			}

			$valueFormatter = new DiffOpValueFormatter(
				$oldClaimHeader,
				$newClaimHeader,
				$oldVal,
				$newVal
			);

			$html .= $valueFormatter->generateHtml();
		}

		return $html;
	}

	/**
	 * Map SnakList
	 *
	 * @param SnakList $snakList
	 * @param callable $mapper
	 *
	 * @return mixed[]
	 */
	private function mapSnakList( SnakList $snakList, $mapper ) {
		$ret = array();
		foreach ( $snakList as $snak ) {
			$ret[] = call_user_func( $mapper, $snak );
		}
		return $ret;
	}

	/**
	 * Get Html for qualifier changes
	 *
	 * @since 0.4
	 *
	 * @param Diff $changes
	 * @param Snak $oldestMainSnak The old main snak, if present; otherwise, the new main snak
	 * @param Snak $newestMainSnak The new main snak, if present; otherwise, the old main snak
	 *
	 * @return string
	 * @throws RuntimeException
	 */
	private function visualizeQualifierChanges(
		Diff $changes,
		Snak $oldestMainSnak,
		Snak $newestMainSnak
	) {
		$html = '';

		$msg = wfMessage( 'wikibase-diffview-qualifier' )->inLanguage( $this->languageCode );
		$oldClaimHeader = $this->snakVisualizer->getPropertyAndValueHeader( $oldestMainSnak ) . ' / ' .
			$msg->parse();
		$newClaimHeader = $this->snakVisualizer->getPropertyAndValueHeader( $newestMainSnak ) . ' / ' .
			$msg->parse();

		foreach ( $changes as $change ) {
			$newVal = $oldVal = null;

			if ( $change instanceof DiffOpAdd || $change instanceof DiffOpChange ) {
				$newVal = $this->snakVisualizer->getPropertyAndDetailedValue( $change->getNewValue() );
			}
			if ( $change instanceof DiffOpRemove || $change instanceof DiffOpChange ) {
				$oldVal = $this->snakVisualizer->getPropertyAndDetailedValue( $change->getOldValue() );
			}

			$valueFormatter = new DiffOpValueFormatter(
					$oldClaimHeader,
					$newClaimHeader,
					$oldVal,
					$newVal
			);

			$html .= $valueFormatter->generateHtml();
		}

		return $html;
	}

}
