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
		$oldMainSnak = null;
		$html = '';

		if ( $claimDifference->getMainSnakChange() !== null ) {
			$html .= $this->visualizeMainSnakChange( $claimDifference->getMainSnakChange() );
			$oldMainSnak = $claimDifference->getMainSnakChange()->getOldValue();
		}

		if ( $claimDifference->getRankChange() !== null ) {
			$html .= $this->visualizeRankChange(
				$claimDifference->getRankChange(),
				$oldMainSnak,
				$baseClaim->getMainSnak()
			);
		}

		if ( $claimDifference->getQualifierChanges() !== null ) {
			$html .= $this->visualizeQualifierChanges(
				$claimDifference->getQualifierChanges(),
				$oldMainSnak,
				$baseClaim->getMainSnak()
			);
		}

		if ( $claimDifference->getReferenceChanges() !== null ) {
			$html .= $this->visualizeSnakListChanges(
				$claimDifference->getReferenceChanges(),
				wfMessage( 'wikibase-diffview-reference' )->inLanguage( $this->languageCode ),
				$oldMainSnak,
				$baseClaim->getMainSnak()
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
	 *
	 * @return string
	 */
	private function visualizeMainSnakChange( DiffOpChange $mainSnakChange ) {
		$oldSnak = $mainSnakChange->getOldValue();
		$newSnak = $mainSnakChange->getNewValue();

		// Does not need to show different headers for left and right side, since the property cannot change
		$headerHtml = $this->snakVisualizer->getPropertyHeader( $newSnak ?: $oldSnak );

		$valueFormatter = new DiffOpValueFormatter(
			// todo: should show specific headers for each column
			$headerHtml,
			// TODO: How to highlight the actual changes inside the snak?
			$this->snakVisualizer->getDetailedValue( $oldSnak ),
			$this->snakVisualizer->getDetailedValue( $newSnak )
		);

		return $valueFormatter->generateHtml();
	}

	/**
	 * Get Html for rank change
	 *
	 * @since 0.4
	 *
	 * @param DiffOpChange $rankChange
	 * @param Snak|null $oldMainSnak
	 * @param Snak|null $newMainSnak
	 *
	 * @return string
	 */
	private function visualizeRankChange( DiffOpChange $rankChange, Snak $oldMainSnak = null, Snak $newMainSnak = null ) {
		// FIXME: Should show different headers for left and right side of the diff
		$claimHeader = $this->snakVisualizer->getPropertyAndValueHeader( $newMainSnak ?: $oldMainSnak );

		$valueFormatter = new DiffOpValueFormatter(
			$claimHeader . ' / ' . wfMessage( 'wikibase-diffview-rank' )->inLanguage( $this->languageCode )->parse(),
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
		$msg = wfMessage( 'wikibase-diffview-rank-' . $rank );
		return $msg->inLanguage( $this->languageCode )->parse();
	}

	/**
	 * Get Html for snaklist changes
	 *
	 * @since 0.4
	 *
	 * @param Diff $changes
	 * @param Message $breadCrumb
	 * @param Snak|null $oldMainSnak
	 * @param Snak|null $newMainSnak
	 *
	 * @return string
	 * @throws RuntimeException
	 */
	private function visualizeSnakListChanges( Diff $changes, Message $breadCrumb, Snak $oldMainSnak = null, Snak $newMainSnak = null ) {
		$html = '';

		$oldClaimHeader = $this->snakVisualizer->getPropertyAndValueHeader( $oldMainSnak ?: $newMainSnak );
		$newClaimHeader = $this->snakVisualizer->getPropertyAndValueHeader( $newMainSnak ?: $oldMainSnak );

		foreach ( $changes as $change ) {
			$newVal = $oldVal = null;
			// FIXME: Should show different headers for left and right side of the diff for DiffOpChanges
			if ( $change instanceof DiffOpAdd || $change instanceof DiffOpChange ) {
				$newVal = $this->mapSnakList(
					$change->getNewValue()->getSnaks(),
					array( $this->snakVisualizer, 'getPropertyAndDetailedValue' )
				);
				$claimHeader = $newClaimHeader;
			}
			if ( $change instanceof DiffOpRemove || $change instanceof DiffOpChange ) {
				$oldVal = $this->mapSnakList(
					$change->getOldValue()->getSnaks(),
					array( $this->snakVisualizer, 'getPropertyAndDetailedValue' )
				);
				$claimHeader = $oldClaimHeader;
			}

			$valueFormatter = new DiffOpValueFormatter(
				$claimHeader . ' / ' . $breadCrumb->parse(),
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
	 * @param Snak|null $oldMainSnak
	 * @param Snak|null $newMainSnak
	 *
	 * @return string
	 * @throws RuntimeException
	 */
	private function visualizeQualifierChanges( Diff $changes, Snak $oldMainSnak = null, Snak $newMainSnak = null ) {
		$html = '';

		$oldClaimHeader = $this->snakVisualizer->getPropertyAndValueHeader( $oldMainSnak ?: $newMainSnak );
		$newClaimHeader = $this->snakVisualizer->getPropertyAndValueHeader( $newMainSnak ?: $oldMainSnak );

		$newVal = $oldVal = null;

		foreach ( $changes as $change ) {
			$newVal = $oldVal = null;

			// FIXME: Should show different headers for left and right side of the diff for DiffOpChanges
			if ( $change instanceof DiffOpAdd || $change instanceof DiffOpChange ) {
				$newVal = $this->snakVisualizer->getPropertyAndDetailedValue( $change->getNewValue() );
				$claimHeader = $newClaimHeader;
			}
			if ( $change instanceof DiffOpRemove || $change instanceof DiffOpChange ) {
				$oldVal = $this->snakVisualizer->getPropertyAndDetailedValue( $change->getOldValue() );
				$claimHeader = $oldClaimHeader;
			}

			$valueFormatter = new DiffOpValueFormatter(
					$claimHeader . ' / ' . wfMessage( 'wikibase-diffview-qualifier' )->inLanguage( $this->languageCode )->parse(),
					$oldVal,
					$newVal
			);

			$html .= $valueFormatter->generateHtml();
		}

		return $html;
	}

}
