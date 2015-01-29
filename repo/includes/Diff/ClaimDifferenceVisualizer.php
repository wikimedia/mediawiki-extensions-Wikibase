<?php

namespace Wikibase\Repo\Diff;

use Diff\Differ\ListDiffer;
use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Exception;
use InvalidArgumentException;
use Message;
use RuntimeException;
use ValueFormatters\FormattingException;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\Serializers\ClaimSerializer;
use Wikibase\Lib\SnakFormatter;

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
 * @author Thiemo Mättig
 */
class ClaimDifferenceVisualizer {

	/**
	 * @var ValueFormatter
	 */
	private $propertyIdFormatter;

	/**
	 * @var SnakFormatter
	 */
	private $snakDetailsFormatter;

	/**
	 * @var SnakFormatter
	 */
	private $snakBreadCrumbFormatter;

	/**
	 * @var string
	 */
	private $languageCode;

	/**
	 * @since 0.4
	 *
	 * @param ValueFormatter $propertyIdFormatter Formatter for IDs, must generate HTML.
	 * @param SnakFormatter $snakDetailsFormatter detailed Formatter for Snaks, must generate HTML.
	 * @param SnakFormatter $snakBreadCrumbFormatter terse Formatter for Snaks, must generate HTML.
	 * @param string $languageCode
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		ValueFormatter $propertyIdFormatter,
		SnakFormatter $snakDetailsFormatter,
		SnakFormatter $snakBreadCrumbFormatter,
		$languageCode
	) {
		if ( $snakDetailsFormatter->getFormat() !== SnakFormatter::FORMAT_HTML
			&& $snakDetailsFormatter->getFormat() !== SnakFormatter::FORMAT_HTML_DIFF
		) {
			throw new InvalidArgumentException(
				'Expected $snakDetailsFormatter to generate html, not '
				. $snakDetailsFormatter->getFormat() );
		}

		if ( $snakBreadCrumbFormatter->getFormat() !== SnakFormatter::FORMAT_HTML
			&& $snakBreadCrumbFormatter->getFormat() !== SnakFormatter::FORMAT_HTML_DIFF
		) {
			throw new InvalidArgumentException(
				'Expected $snakBreadCrumbFormatter to generate html, not '
				. $snakBreadCrumbFormatter->getFormat() );
		}

		$this->propertyIdFormatter = $propertyIdFormatter;
		$this->snakDetailsFormatter = $snakDetailsFormatter;
		$this->snakBreadCrumbFormatter = $snakBreadCrumbFormatter;
		$this->languageCode = $languageCode;
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
		$oldSnak = null;
		$html = '';

		if ( $claimDifference->getMainSnakChange() !== null ) {
			$oldSnak = $claimDifference->getMainSnakChange()->getOldValue();
			$html .= $this->visualizeMainSnakChange( $claimDifference->getMainSnakChange() );
		}

		$rankChange = $claimDifference->getRankChange();
		if ( $rankChange !== null ) {
			$html .= $this->visualizeRankChange(
				$rankChange,
				$oldSnak,
				$baseClaim->getMainSnak()
			);
		}

		$qualifierChanges = $claimDifference->getQualifierChanges();
		if ( $qualifierChanges !== null ) {
			$html .= $this->visualizeQualifierChanges(
				$qualifierChanges,
				$oldSnak,
				$baseClaim->getMainSnak()
			);
		}

		$referenceChanges = $claimDifference->getReferenceChanges();
		if ( $referenceChanges !== null ) {
			$html .= $this->visualizeReferenceChanges(
				$referenceChanges,
				$oldSnak,
				$baseClaim->getMainSnak(),
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
	 * @param DiffOpChange $mainSnakChange
	 *
	 * @return string
	 */
	private function visualizeMainSnakChange( DiffOpChange $mainSnakChange ) {
		$oldSnak = $mainSnakChange->getOldValue();
		$newSnak = $mainSnakChange->getNewValue();

		// FIXME: Should show different headers for left and right side of the diff
		$headerHtml = $this->getSnakLabelHeader( $newSnak ?: $oldSnak );

		$valueFormatter = new DiffOpValueFormatter(
			$headerHtml,
			// TODO: How to highlight the actual changes inside the snak?
			$this->formatSnakDetails( $oldSnak ),
			$this->formatSnakDetails( $newSnak )
		);

		return $valueFormatter->generateHtml();
	}

	/**
	 * Get Html for rank change
	 *
	 * @param DiffOpChange $rankChange
	 * @param Snak|null $oldSnak
	 * @param Snak|null $newSnak
	 *
	 * @return string
	 */
	private function visualizeRankChange(
		DiffOpChange $rankChange,
		Snak $oldSnak = null,
		Snak $newSnak = null
	) {
		// FIXME: Should show different headers for left and right side of the diff
		$claimHeader = $this->getSnakValueHeader( $newSnak ?: $oldSnak );
		$msg = wfMessage( 'wikibase-diffview-rank' );
		$valueFormatter = new DiffOpValueFormatter(
			$claimHeader . ' / ' . $msg->inLanguage( $this->languageCode )->parse(),
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
	 * @param Snak|null $snak
	 *
	 * @return string|null HTML
	 */
	private function formatSnakDetails( Snak $snak = null ) {
		if ( $snak === null ) {
			return null;
		}

		try {
			return $this->snakDetailsFormatter->formatSnak( $snak );
		} catch ( Exception $ex ) {
			// @fixme maybe there is a way we can render something more useful
			// we are getting multiple types of exceptions and should handle
			// consistent (and shared code) with what we do in SnakHtmlGenerator.
			$msg = wfMessage( 'wikibase-snakformat-invalid-value' );
			return $msg->inLanguage( $this->languageCode )->parse();
		}
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return string HTML
	 */
	private function formatPropertyId( EntityId $entityId ) {
		try {
			return $this->propertyIdFormatter->format( $entityId );
		} catch ( FormattingException $ex ) {
			return $entityId->getSerialization();
		}
	}

	/**
	 * @param Reference|null $reference
	 *
	 * @return string[] List of formatted HTML strings of all Snaks in a Reference.
	 */
	private function formatReference( Reference $reference = null ) {
		$values = array();

		if ( $reference === null ) {
			return $values;
		}

		foreach ( $reference->getSnaks() as $snak ) {
			$values[] = $this->formatSnak( $snak );
		}

		return $values;
	}

	/**
	 * @param Snak|null $snak
	 *
	 * @return string Formatted HTML string of a Snak.
	 */
	private function formatSnak( Snak $snak = null ) {
		if ( $snak === null ) {
			return '';
		}

		$msg = wfMessage( 'colon-separator' );
		return $this->formatPropertyId( $snak->getPropertyId() )
			. $msg->inLanguage( $this->languageCode )->escaped()
			. $this->formatSnakDetails( $snak );
	}

	/**
	 * Get formatted header for a snak, including the snak's property label, but not the snak's value.
	 *
	 * @param Snak|null $snak
	 *
	 * @return string HTML
	 */
	private function getSnakLabelHeader( Snak $snak = null ) {
		$msg = wfMessage( 'wikibase-entity-property' );
		$headerText = $msg->inLanguage( $this->languageCode )->parse();

		if ( $snak !== null ) {
			$headerText .= ' / ' . $this->formatPropertyId( $snak->getPropertyId() );
		}

		return $headerText;
	}

	/**
	 * Get formatted header for a snak, including the snak's property label and value.
	 *
	 * @param Snak|null $snak
	 *
	 * @return string HTML
	 */
	private function getSnakValueHeader( Snak $snak = null ) {
		$headerText = $this->getSnakLabelHeader( $snak );

		if ( $snak !== null ) {
			try {
				$msg = wfMessage( 'colon-separator' );
				$headerText .= $msg->inLanguage( $this->languageCode )->escaped()
					. $this->snakBreadCrumbFormatter->formatSnak( $snak );
			} catch ( Exception $ex ) {
				// just ignore it
			}
		}

		return $headerText;
	}

	/**
	 * Get Html for snaklist changes
	 *
	 * @param Diff $changes
	 * @param Snak|null $oldSnak
	 * @param Snak|null $newSnak
	 * @param Message $breadCrumb
	 *
	 * @return string
	 * @throws RuntimeException
	 */
	private function visualizeReferenceChanges(
		Diff $changes,
		Snak $oldSnak = null,
		Snak $newSnak = null,
		Message $breadCrumb
	) {
		$html = '';

		$oldClaimHeader = $this->getSnakValueHeader( $oldSnak ?: $newSnak );
		$newClaimHeader = $this->getSnakValueHeader( $newSnak ?: $oldSnak );

		foreach ( $changes as $change ) {
			$oldVal = null;
			$newVal = null;

			if ( $change instanceof DiffOpAdd ) {
				$claimHeader = $newClaimHeader;

				$newVal = $this->formatReference( $change->getNewValue() );
			} else if ( $change instanceof DiffOpRemove ) {
				$claimHeader = $oldClaimHeader;

				$oldVal = $this->formatReference( $change->getOldValue() );
			} elseif ( $change instanceof DiffOpChange ) {
				// FIXME: Should show different headers for left and right side of the diff
				$claimHeader = $newClaimHeader;

				$oldVal = $this->formatReference( $change->getOldValue() );
				$newVal = $this->formatReference( $change->getNewValue() );
			} else {
				throw new RuntimeException( 'Diff operation of unknown type ' . gettype( $change ) );
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
	 * Get Html for qualifier changes
	 *
	 * @param Diff $changes
	 * @param Snak|null $oldSnak
	 * @param Snak|null $newSnak
	 *
	 * @return string
	 * @throws RuntimeException
	 */
	private function visualizeQualifierChanges(
		Diff $changes,
		Snak $oldSnak = null,
		Snak $newSnak = null
	) {
		$html = '';

		$oldClaimHeader = $this->getSnakValueHeader( $oldSnak ?: $newSnak );
		$newClaimHeader = $this->getSnakValueHeader( $newSnak ?: $oldSnak );

		foreach ( $changes as $change ) {
			$oldVal = null;
			$newVal = null;

			if ( $change instanceof DiffOpAdd ) {
				$claimHeader = $newClaimHeader;

				$newVal = $this->formatSnak( $change->getNewValue() );
			} else if ( $change instanceof DiffOpRemove ) {
				$claimHeader = $oldClaimHeader;

				$oldVal = $this->formatSnak( $change->getOldValue() );
			} else if ( $change instanceof DiffOpChange ) {
				// FIXME: Should show different headers for left and right side of the diff
				$claimHeader = $newClaimHeader;

				$oldVal = $this->formatSnak( $change->getOldValue() );
				$newVal = $this->formatSnak( $change->getNewValue() );
			} else {
				throw new RuntimeException( 'Diff operation of unknown type ' . gettype( $change ) );
			}

			$msg = wfMessage( 'wikibase-diffview-qualifier' );
			$valueFormatter = new DiffOpValueFormatter(
				$claimHeader . ' / ' . $msg->inLanguage( $this->languageCode )->parse(),
				$oldVal,
				$newVal
			);

			$html .= $valueFormatter->generateHtml();
		}

		return $html;
	}

}
