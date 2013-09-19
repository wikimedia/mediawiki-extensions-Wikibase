<?php
namespace Wikibase;

use DataValues\TimeValue;
use Diff\DiffOpAdd;
use Diff\DiffOpChange;
use Diff\DiffOpRemove;
use Html;
use Diff\Diff;
use RuntimeException;
use ValueParsers\FormattingException;
use Wikibase\Lib\EntityIdLabelFormatter;
use Wikibase\Lib\SnakFormatter;

/**
 * Class for generating HTML for Claim Diffs.
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ClaimDifferenceVisualizer {

	/**
	 * @since 0.5
	 *
	 * @var EntityIdLabelFormatter
	 */
	private $propertyIdFormatter;

	/**
	 * @since 0.5
	 *
	 * @var SnakFormatter
	 */
	private $snakFormatter;

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param EntityIdLabelFormatter $propertyIdFormatter
	 * @param SnakFormatter          $snakFormatter
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct( EntityIdLabelFormatter $propertyIdFormatter, SnakFormatter $snakFormatter ) {
		if ( $snakFormatter->getFormat() !== SnakFormatter::FORMAT_PLAIN ) {
			throw new \InvalidArgumentException(
				'Expected $snakFormatter to generate plain text, not '
				. $snakFormatter->getFormat() );
		}

		$this->propertyIdFormatter = $propertyIdFormatter;
		$this->snakFormatter = $snakFormatter;
	}

	/**
	 * Generates HTML of a claim change.
	 * @since 0.4
	 *
	 * @param ClaimDifference $claimDifference
	 * @param Claim $baseClaim
	 *
	 * @return string
	 */
	public function visualizeClaimChange( ClaimDifference $claimDifference, Claim $baseClaim ) {
		$html = '';

		if ( $claimDifference->getMainSnakChange() !== null ) {
			$html .= $this->visualizeMainSnakChange( $claimDifference->getMainSnakChange() );
		}

		if ( $claimDifference->getRankChange() !== null ) {
			$html .= $this->visualizeRankChange( $claimDifference->getRankChange() );
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
				wfMessage( 'wikibase-diffview-reference' )
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
		$mainSnak = $claim->getMainSnak();

		$html = '';

		$html .= $this->getSnakHtml(
			null,
			$mainSnak
		);

		return $html;
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
		$mainSnak = $claim->getMainSnak();

		$html = '';

		$html .= $this->getSnakHtml(
			$mainSnak,
			null
		);

		return $html;
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
	protected function visualizeMainSnakChange( DiffOpChange $mainSnakChange ) {
		$valueFormatter = new DiffOpValueFormatter(
			// todo: should show specific headers for both columns
			$this->getSnakHeader( $mainSnakChange->getNewValue() ),
			$this->formatSnak( $mainSnakChange->getOldValue() ),
			$this->formatSnak( $mainSnakChange->getNewValue() )
		);

		return $valueFormatter->generateHtml();
	}

	/**
	 * Get Html for rank change
	 *
	 * @since 0.4
	 *
	 * @param DiffOpChange $rankChange
	 *
	 * @return string
	 */
	protected function visualizeRankChange( DiffOpChange $rankChange ) {
		$valueFormatter = new DiffOpValueFormatter(
			wfMessage( 'wikibase-diffview-rank' ),
			$rankChange->getOldValue(),
			$rankChange->getNewValue()
		);
		return $valueFormatter->generateHtml();
	}

	/**
	 * Format a snak
	 *
	 * @since 0.4
	 *
	 * @param Snak|null $oldSnak
	 * @param Snak|null $newSnak
	 * @param string|null $prependHeader
	 *
	 * @throws \MWException
	 * @return string
	 */
	public function getSnakHtml( $oldSnak, $newSnak, $prependHeader = null ) {
		$snakHeader = '';
		// @todo fix ugly cruft!
		if ( $prependHeader !== null ) {
			$snakHeader = $prependHeader;
		}

		if ( $newSnak instanceof Snak || $oldSnak instanceof Snak ) {
			$headerSnak = $newSnak instanceof Snak ? $newSnak : $oldSnak;
			$snakHeader .= $this->getSnakHeader( $headerSnak );
		} else {
			// something went wrong
			throw new \MWException( 'Snak parameters not provided.' );
		}

		$oldValue = null;
		$newValue = null;

		if ( $oldSnak instanceof Snak ) {
			$oldValue = $this->formatSnak( $oldSnak );
		}

		if ( $newSnak instanceof Snak ) {
			$newValue = $this->formatSnak( $newSnak );
		}

		$valueFormatter = new DiffOpValueFormatter( $snakHeader, $oldValue, $newValue );

		return $valueFormatter->generateHtml();
	}

	/**
	 * @param Snak $snak
	 *
	 * @return string
	 */
	protected function formatSnak( Snak $snak ) {
		try {
			return $this->snakFormatter->formatSnak( $snak );
		} catch ( FormattingException $ex ) {
			return '?'; // XXX: or include the error message?
		}
	}

	/**
	 * @param EntityId
	 *
	 * @return string
	 */
	protected function formatPropertyId( EntityId $id ) {
		try {
			return $this->propertyIdFormatter->format( $id );
		} catch ( FormattingException $ex ) {
			return '?'; // XXX: or include the error message?
		}
	}

	/**
	 * Get formatted values of SnakList in an array
	 *
	 * @since 0.4
	 *
	 * @param SnakList $snakList
	 *
	 * @return string[]
	 */
	 protected function getSnakListValues( SnakList $snakList ) {
		$values = array();

		foreach ( $snakList as $snak ) {
			// TODO: change hardcoded ": " so something like wfMessage( 'colon-separator' ),
			// but this will require further refactoring as it would add HTML which gets escaped
			$values[] =
				$this->formatPropertyId( $snak->getPropertyId() ) .
				': '.
				$this->formatSnak( $snak );
		}

		return $values;
	}

	/**
	 * Get formatted header for a snak
	 *
	 * @since 0.4
	 *
	 * @param Snak $snak
	 *
	 * @return string
 	 */
	protected function getSnakHeader( Snak $snak ) {
		$propertyId = $snak->getPropertyId();
		$propertyLabel = $this->formatPropertyId( $propertyId );
		$headerText = wfMessage( 'wikibase-entity-property' ) . ' / ' . $propertyLabel;

		return $headerText;
	}

	/**
	 * Get Html for snaklist changes
	 *
	 * @since 0.4
	 *
	 * @param Diff[] $changes
	 * @param Claim $claim
	 * @param string $breadCrumb
	 *
	 * @return string
	 * @throws RuntimeException
	 */
	protected function visualizeSnakListChanges( Diff $changes, Claim $claim, $breadCrumb ) {
		$html = '';

		$claimMainSnak = $claim->getMainSnak();
		$claimHeader = $this->getSnakHeader( $claimMainSnak );

		$newVal = null;
		$oldVal = null;

		foreach( $changes as $change ) {
			if ( $change instanceof DiffOpAdd ) {
				$newVal = $this->getSnakListValues( $change->getNewValue()->getSnaks() );
			} else if ( $change instanceof DiffOpRemove ) {
				$oldVal = $this->getSnakListValues( $change->getOldValue()->getSnaks() );
			} else if ( $change instanceof DiffOpChange ) {
				$oldVal = $this->getSnakListValues( $change->getOldValue()->getSnaks() );
				$newVal = $this->getSnakListValues( $change->getNewValue()->getSnaks() );
			} else {
				throw new RuntimeException( 'Diff operation of unknown type.' );
			}

			$valueFormatter = new DiffOpValueFormatter(
				$claimHeader . ' / ' . $breadCrumb,
				$oldVal,
				$newVal
			);

			$oldVal = $newVal = null;
			$html .= $valueFormatter->generateHtml();
		}

		return $html;
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
	protected function visualizeQualifierChanges( Diff $changes, Claim $claim ) {
		$html = '';

		$claimMainSnak = $claim->getMainSnak();
		$claimHeader = $this->getSnakHeader( $claimMainSnak );
		$newVal = $oldVal = null;

		foreach( $changes as $change ) {
			// TODO: change hardcoded ": " so something like wfMessage( 'colon-separator' ),
			// but this will require further refactoring as it would add HTML which gets escaped
			if ( $change instanceof DiffOpAdd ) {
				$newVal =
					$this->formatPropertyId( $change->getNewValue()->getPropertyId() ) .
					': ' .
					$this->formatSnak( $change->getNewValue() );
			} else if ( $change instanceof DiffOpRemove ) {
				$oldVal =
					$this->formatPropertyId( $change->getOldValue()->getPropertyId() ) .
					': ' .
					$this->formatSnak( $change->getOldValue() );
			} else if ( $change instanceof DiffOpChange ) {
				$oldVal =
					$this->formatPropertyId( $change->getOldValue()->getPropertyId() ) .
					': ' .
					$this->formatSnak( $change->getOldValue() );
				$newVal =
					$this->formatPropertyId( $change->getNewValue()->getPropertyId() ) .
					': ' .
					$this->formatSnak( $change->getNewValue() );
			} else {
				throw new RuntimeException( 'Diff operation of unknown type.' );
			}

			$valueFormatter = new DiffOpValueFormatter(
					$claimHeader . ' / ' . wfMessage( 'wikibase-diffview-qualifier' ),
					$oldVal,
					$newVal
			);

			$oldVal = $newVal = null;
			$html .= $valueFormatter->generateHtml();
		}

		return $html;
	}

}
