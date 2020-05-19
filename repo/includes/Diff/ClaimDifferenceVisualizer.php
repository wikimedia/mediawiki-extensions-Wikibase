<?php

namespace Wikibase\Repo\Diff;

use Diff\Differ\ListDiffer;
use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\StatementRankSerializer;

/**
 * Class for generating HTML for Claim Diffs.
 *
 * @license GPL-2.0-or-later
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
	 * Generates HTML of a statement change.
	 *
	 * @param ClaimDifference $claimDifference
	 * @param Statement $baseStatement The new statement, if it exists. Otherwise the old statement.
	 * @param string[] $path The path to prepend in the header
	 *
	 * @return string HTML
	 */
	public function visualizeClaimChange(
		ClaimDifference $claimDifference,
		Statement $baseStatement,
		array $path = []
	) {
		$headerPrefix = $path ? implode( ' / ', $path ) . ' / ' : '';
		$newestMainSnak = $baseStatement->getMainSnak();
		$oldestMainSnak = $newestMainSnak;
		$html = '';

		$mainSnakChange = $claimDifference->getMainSnakChange();
		if ( $mainSnakChange !== null ) {
			$oldestMainSnak = $mainSnakChange->getOldValue() ?: $newestMainSnak;
			$html .= $this->visualizeMainSnakChange(
				$headerPrefix,
				$mainSnakChange,
				$oldestMainSnak,
				$newestMainSnak
			);
		}

		$rankChange = $claimDifference->getRankChange();
		if ( $rankChange !== null ) {
			$html .= $this->visualizeRankChange(
				$headerPrefix,
				$rankChange,
				$oldestMainSnak,
				$newestMainSnak
			);
		}

		$qualifierChanges = $claimDifference->getQualifierChanges();
		if ( $qualifierChanges !== null ) {
			$html .= $this->visualizeQualifierChanges(
				$headerPrefix,
				$qualifierChanges,
				$oldestMainSnak,
				$newestMainSnak
			);
		}

		$referenceChanges = $claimDifference->getReferenceChanges();
		if ( $referenceChanges !== null ) {
			$html .= $this->visualizeReferenceChanges(
				$headerPrefix,
				$referenceChanges,
				$oldestMainSnak,
				$newestMainSnak
			);
		}

		return $html;
	}

	/**
	 * Get diff html for a new claim
	 *
	 * @param Statement $statement
	 * @param string[] $path The path to prepend in the header
	 *
	 * @return string HTML
	 */
	public function visualizeNewClaim( Statement $statement, array $path = [] ) {
		$claimDiffer = new ClaimDiffer( new ListDiffer() );
		$claimDifference = $claimDiffer->diffClaims( null, $statement );
		return $this->visualizeClaimChange( $claimDifference, $statement, $path );
	}

	/**
	 * Get diff html for a removed claim
	 *
	 * @param Statement $statement
	 * @param string[] $path The path to prepend in the header
	 *
	 * @return string HTML
	 */
	public function visualizeRemovedClaim( Statement $statement, array $path = [] ) {
		$claimDiffer = new ClaimDiffer( new ListDiffer() );
		$claimDifference = $claimDiffer->diffClaims( $statement, null );
		return $this->visualizeClaimChange( $claimDifference, $statement, $path );
	}

	/**
	 * @param string $headerPrefix
	 * @param DiffOpChange $mainSnakChange
	 * @param Snak $oldestMainSnak The old main snak, if present; otherwise, the new main snak
	 * @param Snak $newestMainSnak The new main snak, if present; otherwise, the old main snak
	 *
	 * @return string HTML
	 */
	private function visualizeMainSnakChange(
		$headerPrefix,
		DiffOpChange $mainSnakChange,
		Snak $oldestMainSnak,
		Snak $newestMainSnak
	) {
		$valueFormatter = new DiffOpValueFormatter(
			$headerPrefix . $this->snakVisualizer->getPropertyHeader( $oldestMainSnak ),
			$headerPrefix . $this->snakVisualizer->getPropertyHeader( $newestMainSnak ),
			// TODO: How to highlight the actual changes inside the snak?
			$this->snakVisualizer->getDetailedValue( $mainSnakChange->getOldValue() ),
			$this->snakVisualizer->getDetailedValue( $mainSnakChange->getNewValue() )
		);

		return $valueFormatter->generateHtml();
	}

	/**
	 * @param string $headerPrefix
	 * @param DiffOpChange $rankChange
	 * @param Snak $oldestMainSnak The old main snak, if present; otherwise, the new main snak
	 * @param Snak $newestMainSnak The new main snak, if present; otherwise, the old main snak
	 *
	 * @return string HTML
	 */
	private function visualizeRankChange(
		$headerPrefix,
		DiffOpChange $rankChange,
		Snak $oldestMainSnak,
		Snak $newestMainSnak
	) {
		$msg = wfMessage( 'wikibase-diffview-rank' )->inLanguage( $this->languageCode );
		$header = $msg->parse();

		$valueFormatter = new DiffOpValueFormatter(
			$headerPrefix . $this->snakVisualizer->getPropertyAndValueHeader( $oldestMainSnak )
				. ' / ' . $header,
			$headerPrefix . $this->snakVisualizer->getPropertyAndValueHeader( $newestMainSnak )
				. ' / ' . $header,
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
			$statementRankSerializer = new StatementRankSerializer();
			$rank = $statementRankSerializer->serialize( $rank );
		}

		// Messages:
		// wikibase-diffview-rank-deprecated
		// wikibase-diffview-rank-normal
		// wikibase-diffview-rank-preferred
		$msg = wfMessage( 'wikibase-diffview-rank-' . $rank )->inLanguage( $this->languageCode );
		return $msg->parse();
	}

	/**
	 * @param string $headerPrefix
	 * @param Diff $changes
	 * @param Snak $oldestMainSnak The old main snak, if present; otherwise, the new main snak
	 * @param Snak $newestMainSnak The new main snak, if present; otherwise, the old main snak
	 *
	 * @return string HTML
	 */
	private function visualizeReferenceChanges(
		$headerPrefix,
		Diff $changes,
		Snak $oldestMainSnak,
		Snak $newestMainSnak
	) {
		$html = '';

		$msg = wfMessage( 'wikibase-diffview-reference' )->inLanguage( $this->languageCode );
		$header = $msg->parse();

		$oldClaimHeader = $headerPrefix
			. $this->snakVisualizer->getPropertyAndValueHeader( $oldestMainSnak )
			. ' / ' . $header;
		$newClaimHeader = $headerPrefix
			. $this->snakVisualizer->getPropertyAndValueHeader( $newestMainSnak )
			. ' / ' . $header;

		foreach ( $changes as $change ) {
			$oldValue = null;
			$newValue = null;

			if ( $change instanceof DiffOpAdd ) {
				$newValue = $change->getNewValue();
			} elseif ( $change instanceof DiffOpChange ) {
				$oldValue = $change->getOldValue();
				$newValue = $change->getNewValue();
			} elseif ( $change instanceof DiffOpRemove ) {
				$oldValue = $change->getOldValue();
			}

			$oldValuesHtml = $oldValue !== null
				? $this->visualizeSnaks( $oldValue->getSnaks() )
				: null;
			$newValuesHtml = $newValue !== null
				? $this->visualizeSnaks( $newValue->getSnaks() )
				: null;

			$valueFormatter = new DiffOpValueFormatter(
				$oldClaimHeader,
				$newClaimHeader,
				$oldValuesHtml,
				$newValuesHtml
			);

			$html .= $valueFormatter->generateHtml();
		}

		return $html;
	}

	/**
	 * @param SnakList $snaks
	 *
	 * @return string[] HTML
	 */
	private function visualizeSnaks( SnakList $snaks ) {
		$html = [];

		foreach ( $snaks as $snak ) {
			$html[] = $this->snakVisualizer->getPropertyAndDetailedValue( $snak );
		}

		return $html;
	}

	/**
	 * @param string $headerPrefix
	 * @param Diff $changes
	 * @param Snak $oldestMainSnak The old main snak, if present; otherwise, the new main snak
	 * @param Snak $newestMainSnak The new main snak, if present; otherwise, the old main snak
	 *
	 * @return string HTML
	 */
	private function visualizeQualifierChanges(
		$headerPrefix,
		Diff $changes,
		Snak $oldestMainSnak,
		Snak $newestMainSnak
	) {
		$html = '';

		$msg = wfMessage( 'wikibase-diffview-qualifier' )->inLanguage( $this->languageCode );
		$header = $msg->parse();

		$oldClaimHeader = $headerPrefix
			. $this->snakVisualizer->getPropertyAndValueHeader( $oldestMainSnak )
			. ' / ' . $header;
		$newClaimHeader = $headerPrefix
			. $this->snakVisualizer->getPropertyAndValueHeader( $newestMainSnak )
			. ' / ' . $header;

		foreach ( $changes as $change ) {
			$oldValue = null;
			$newValue = null;

			if ( $change instanceof DiffOpAdd ) {
				$newValue = $change->getNewValue();
			} elseif ( $change instanceof DiffOpChange ) {
				$oldValue = $change->getOldValue();
				$newValue = $change->getNewValue();
			} elseif ( $change instanceof DiffOpRemove ) {
				$oldValue = $change->getOldValue();
			}

			$oldValueHtml = $oldValue !== null
				? $this->snakVisualizer->getPropertyAndDetailedValue( $oldValue )
				: null;
			$newValueHtml = $newValue !== null
				? $this->snakVisualizer->getPropertyAndDetailedValue( $newValue )
				: null;

			$valueFormatter = new DiffOpValueFormatter(
				$oldClaimHeader,
				$newClaimHeader,
				$oldValueHtml,
				$newValueHtml
			);

			$html .= $valueFormatter->generateHtml();
		}

		return $html;
	}

}
