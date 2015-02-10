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
		$html = '';

		if ( $claimDifference->getMainSnakChange() !== null ) {
			$html .= $this->visualizeMainSnakChange( $claimDifference->getMainSnakChange() );
		}

		if ( $claimDifference->getRankChange() !== null ) {
			$html .= $this->visualizeRankChange( $claimDifference->getRankChange(), $baseClaim );
		}

		if ( $claimDifference->getQualifierChanges() !== null ) {
			$html .= $this->visualizeQualifierChanges(
				$claimDifference->getQualifierChanges(),
				$baseClaim
			);
		}

		if ( $claimDifference->getReferenceChanges() !== null ) {
			$html .= $this->visualizeSnakListChanges(
				$claimDifference->getReferenceChanges(),
				$baseClaim,
				wfMessage( 'wikibase-diffview-reference' )->inLanguage( $this->languageCode )
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

		if ( $newSnak === null ) {
			$headerHtml = $this->snakVisualizer->getPropertyHeader( $oldSnak );
		} else {
			$headerHtml = $this->snakVisualizer->getPropertyHeader( $newSnak );
		}

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
	 * @param Claim $claim the claim, as context for display
	 *
	 * @return string
	 */
	private function visualizeRankChange( DiffOpChange $rankChange, Claim $claim ) {
		$claimMainSnak = $claim->getMainSnak();
		$claimHeader = $this->snakVisualizer->getPropertyAndValueHeader( $claimMainSnak );

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
	 * @return null|String HTML
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
	 * @param Claim $claim
	 * @param Message $breadCrumb
	 *
	 * @return string
	 * @throws RuntimeException
	 */
	private function visualizeSnakListChanges( Diff $changes, Claim $claim, Message $breadCrumb ) {
		$html = '';

		$claimMainSnak = $claim->getMainSnak();
		$claimHeader = $this->snakVisualizer->getPropertyAndValueHeader( $claimMainSnak );

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
	 * @param Claim $claim
	 *
	 * @return string
	 * @throws RuntimeException
	 */
	private function visualizeQualifierChanges( Diff $changes, Claim $claim ) {
		$html = '';

		$claimMainSnak = $claim->getMainSnak();
		$claimHeader = $this->snakVisualizer->getPropertyAndValueHeader( $claimMainSnak );

		foreach ( $changes as $change ) {
			$newVal = $oldVal = null;

			if ( $change instanceof DiffOpAdd || $change instanceof DiffOpChange ) {
				$newVal = $this->snakVisualizer->getPropertyAndDetailedValue( $change->getNewValue() );
			}
			if ( $change instanceof DiffOpRemove || $change instanceof DiffOpChange ) {
				$oldVal = $this->snakVisualizer->getPropertyAndDetailedValue( $change->getOldValue() );
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
