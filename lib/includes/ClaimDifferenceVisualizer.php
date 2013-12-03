<?php
namespace Wikibase;

use Diff\DiffOpAdd;
use Diff\DiffOpChange;
use Diff\DiffOpRemove;
use Diff\ListDiffer;
use Diff\Diff;
use InvalidArgumentException;
use Message;
use RuntimeException;
use ValueFormatters\ValueFormatter;
use ValueParsers\FormattingException;
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
	private $langCode;

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param ValueFormatter $propertyIdFormatter Formatter for IDs, must generate HTML.
	 * @param SnakFormatter $snakDetailsFormatter detailed Formatter for Snaks, must generate HTML.
	 * @param SnakFormatter $snakBreadCrumbFormatter terse Formatter for Snaks, must generate HTML.
	 * @param $langCode
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( ValueFormatter $propertyIdFormatter,
		SnakFormatter $snakDetailsFormatter, SnakFormatter $snakBreadCrumbFormatter, $langCode
	) {
		if ( $snakDetailsFormatter->getFormat() !== SnakFormatter::FORMAT_HTML
			&& $snakDetailsFormatter->getFormat() !== SnakFormatter::FORMAT_HTML_DIFF ) {

			throw new InvalidArgumentException(
				'Expected $snakDetailsFormatter to generate html, not '
				. $snakDetailsFormatter->getFormat() );
		}

		if ( $snakBreadCrumbFormatter->getFormat() !== SnakFormatter::FORMAT_HTML
			&& $snakBreadCrumbFormatter->getFormat() !== SnakFormatter::FORMAT_HTML_DIFF ) {

			throw new InvalidArgumentException(
				'Expected $snakBreadCrumbFormatter to generate html, not '
				. $snakBreadCrumbFormatter->getFormat() );
		}

		$this->propertyIdFormatter = $propertyIdFormatter;
		$this->snakDetailsFormatter = $snakDetailsFormatter;
		$this->snakBreadCrumbFormatter = $snakBreadCrumbFormatter;

		$this->langCode = $langCode;
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
				wfMessage( 'wikibase-diffview-reference' )->inLanguage( $this->langCode )
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
		if( $mainSnakChange->getNewValue() === null ){
			$headerHtml = $this->getSnakLabelHeader( $mainSnakChange->getOldValue() );
		} else {
			$headerHtml = $this->getSnakLabelHeader( $mainSnakChange->getNewValue() );
		}

		$valueFormatter = new DiffOpValueFormatter(
			// todo: should show specific headers for each column
			$headerHtml,
			$this->formatSnakDetails( $mainSnakChange->getOldValue() ),
			$this->formatSnakDetails( $mainSnakChange->getNewValue() )
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
			$claimHeader . ' / ' . wfMessage( 'wikibase-diffview-rank' )->inLanguage( $this->langCode )->parse(),
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

		$msg = wfMessage( 'wikibase-diffview-rank-' . $rank );
		return $msg->inLanguage( $this->langCode )->parse();
	}

	/**
	 * @param Snak|null $snak
	 *
	 * @return string HTML
	 */
	protected function formatSnakDetails( $snak ) {
		if( $snak === null ){
			return null;
		}
		try {
			return $this->snakDetailsFormatter->formatSnak( $snak );
		} catch ( FormattingException $ex ) {
			return '?'; // XXX: or include the error message?
		}
	}

	/**
	 * @param EntityId
	 *
	 * @return string HTML
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
	 * @return string[] A list if HTML strings
	 */
	 protected function getSnakListValues( SnakList $snakList ) {
		$values = array();
		$colon = wfMessage( 'colon-separator' )->inLanguage( $this->langCode )->escaped();

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
	 * @param Snak $snak
	 *
	 * @return string HTML
	 */
	protected function getSnakLabelHeader( Snak $snak ) {
		$propertyId = $snak->getPropertyId();
		$propertyLabel = $this->formatPropertyId( $propertyId );

		$headerText = wfMessage( 'wikibase-entity-property' )->inLanguage( $this->langCode )->parse()
			. ' / ' . $propertyLabel ;

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
			$headerText .= wfMessage( 'colon-separator' )->inLanguage( $this->langCode )->escaped()
				. $this->snakBreadCrumbFormatter->formatSnak( $snak );
		} catch ( FormattingException $ex ) {
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
		$colon = wfMessage( 'colon-separator' )->inLanguage( $this->langCode )->escaped();

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
					$claimHeader . ' / ' . wfMessage( 'wikibase-diffview-qualifier' )->inLanguage( $this->langCode )->parse(),
					$oldVal,
					$newVal
			);

			$oldVal = $newVal = null;
			$html .= $valueFormatter->generateHtml();
		}

		return $html;
	}

}
