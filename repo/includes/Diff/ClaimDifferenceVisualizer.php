<?php

namespace Wikibase\Repo\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Diff\Differ\ListDiffer;
use Exception;
use InvalidArgumentException;
use Message;
use RuntimeException;
use ValueFormatters\FormattingException;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
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
 */
class ClaimDifferenceVisualizer {

	/**
	 * @since 0.5
	 *
	 * @var ValueFormatter
	 */
	private $propertyIdFormatter;

	/**
	 * @since 0.5
	 *
	 * @var SnakFormatter
	 */
	private $snakDetailsFormatter;

	/**
	 * @since 0.5
	 *
	 * @var SnakFormatter
	 */
	private $snakBreadCrumbFormatter;

	/**
	 * @since 0.5
	 *
	 * @var string
	 */
	private $languageCode;

	/**
	 * Constructor.
	 *
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
	protected function visualizeMainSnakChange( DiffOpChange $mainSnakChange ) {
		$oldSnak = $mainSnakChange->getOldValue();
		$newSnak = $mainSnakChange->getNewValue();

		if ( $newSnak === null ) {
			$headerHtml = $this->getSnakLabelHeader( $oldSnak );
		} else {
			$headerHtml = $this->getSnakLabelHeader( $newSnak );
		}

		$valueFormatter = new DiffOpValueFormatter(
			// todo: should show specific headers for each column
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
	 * @since 0.4
	 *
	 * @param DiffOpChange $rankChange
	 * @param Claim $claim the claim, as context for display
	 *
	 * @return string
	 */
	protected function visualizeRankChange( DiffOpChange $rankChange, Claim $claim ) {
		$claimMainSnak = $claim->getMainSnak();
		$claimHeader = $this->getSnakValueHeader( $claimMainSnak );

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
	protected function getRankHtml( $rank ) {
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
	 * @return string HTML
	 */
	protected function formatSnakDetails( Snak $snak = null ) {
		if ( $snak === null ) {
			return null;
		}

		try {
			return $this->snakDetailsFormatter->formatSnak( $snak );
		} catch ( Exception $ex ) {
			// @fixme maybe there is a way we can render something more useful
			// we are getting multiple types of exceptions and should handle
			// consistent (and shared code) with what we do in SnakHtmlGenerator.
			$messageText = wfMessage( 'wikibase-snakformat-invalid-value' )
				->inLanguage( $this->languageCode )
				->parse();

			return $messageText;
		}
	}

	/**
	 * @param EntityId
	 *
	 * @return string HTML
	 */
	protected function formatPropertyId( EntityId $entityId ) {
		try {
			return $this->propertyIdFormatter->format( $entityId );
		} catch ( FormattingException $ex ) {
			return $entityId->getSerialization(); // XXX: or include the error message?
		}
	}

	/**
	 * Get formatted values of SnakList in an array
	 *
	 * @since 0.4
	 *
	 * @param SnakList $snakList
	 *
	 * @return string[] A list if HTML strings
	 */
	 protected function getSnakListValues( SnakList $snakList ) {
		$values = array();
		$colon = wfMessage( 'colon-separator' )->inLanguage( $this->languageCode )->escaped();

		foreach ( $snakList as $snak ) {
			/** @var $snak Snak */
			$values[] =
				$this->formatPropertyId( $snak->getPropertyId() ) .
				$colon.
				$this->formatSnakDetails( $snak );
		}

		return $values;
	}

	/**
	 * Get formatted header for a snak, including the snak's property label, but not the snak's value.
	 *
	 * @since 0.4
	 *
	 * @param Snak|null $snak
	 *
	 * @return string HTML
	 */
	protected function getSnakLabelHeader( Snak $snak = null ) {
		$headerText = wfMessage( 'wikibase-entity-property' )->inLanguage( $this->languageCode )->parse();

		if ( $snak !== null ) {
			$propertyId = $snak->getPropertyId();
			$headerText .= ' / ' . $this->formatPropertyId( $propertyId );
		}

		return $headerText;
	}

	/**
	 * Get formatted header for a snak, including the snak's property label and value.
	 *
	 * @since 0.4
	 *
	 * @param Snak $snak
	 *
	 * @return string HTML
	 */
	protected function getSnakValueHeader( Snak $snak ) {
		$headerText = $this->getSnakLabelHeader( $snak );

		try {
			$headerText .= wfMessage( 'colon-separator' )->inLanguage( $this->languageCode )->escaped()
				. $this->snakBreadCrumbFormatter->formatSnak( $snak );
		} catch ( Exception $ex ) {
			// just ignore it
		}

		return $headerText;
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
	protected function visualizeSnakListChanges( Diff $changes, Claim $claim, Message $breadCrumb ) {
		$html = '';

		$claimMainSnak = $claim->getMainSnak();
		$claimHeader = $this->getSnakValueHeader( $claimMainSnak );

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
				$claimHeader . ' / ' . $breadCrumb->parse(),
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
		$colon = wfMessage( 'colon-separator' )->inLanguage( $this->languageCode )->escaped();

		$claimMainSnak = $claim->getMainSnak();
		$claimHeader = $this->getSnakValueHeader( $claimMainSnak );
		$newVal = $oldVal = null;

		foreach( $changes as $change ) {
			if ( $change instanceof DiffOpAdd ) {
				$newVal =
					$this->formatPropertyId( $change->getNewValue()->getPropertyId() ) .
					$colon .
					$this->formatSnakDetails( $change->getNewValue() );
			} else if ( $change instanceof DiffOpRemove ) {
				$oldVal =
					$this->formatPropertyId( $change->getOldValue()->getPropertyId() ) .
					$colon .
					$this->formatSnakDetails( $change->getOldValue() );
			} else if ( $change instanceof DiffOpChange ) {
				$oldVal =
					$this->formatPropertyId( $change->getOldValue()->getPropertyId() ) .
					$colon .
					$this->formatSnakDetails( $change->getOldValue() );
				$newVal =
					$this->formatPropertyId( $change->getNewValue()->getPropertyId() ) .
					$colon .
					$this->formatSnakDetails( $change->getNewValue() );
			} else {
				throw new RuntimeException( 'Diff operation of unknown type.' );
			}

			$valueFormatter = new DiffOpValueFormatter(
					$claimHeader . ' / ' . wfMessage( 'wikibase-diffview-qualifier' )->inLanguage( $this->languageCode )->parse(),
					$oldVal,
					$newVal
			);

			$oldVal = $newVal = null;
			$html .= $valueFormatter->generateHtml();
		}

		return $html;
	}

}
